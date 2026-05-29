<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Recommendation;
use App\Entity\User;
use App\Repository\DepartmentRepository;
use App\Repository\MeetingRepository;
use App\Repository\RecommendationRepository;
use App\Repository\StructureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    /**
     * Point d'entrée : aiguille chaque utilisateur vers le dashboard de son rôle.
     */
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $role = $user ? $user->getMainRole() : 'ROLE_AGENT';

        return match ($role) {
            'ROLE_ADMIN', 'ROLE_DG' => $this->redirectToRoute('app_dashboard_admin'),
'ROLE_COORDINATOR' => $this->redirectToRoute('app_dashboard_coordinator'),
            'ROLE_SECRETARY' => $this->redirectToRoute('app_dashboard_secretary'),
            'ROLE_CHIEF_STRUCTURE' => $this->redirectToRoute('app_dashboard_structure'),
            'ROLE_CHIEF_SERVICE' => $this->redirectToRoute('app_dashboard_service'),
            'ROLE_FOLLOWUP' => $this->redirectToRoute('app_dashboard_followup'),
            default => $this->redirectToRoute('app_dashboard_agent'),
        };
    }

 /**
     * Dashboard ADMINISTRATEUR / DG — vue globale stratégique avec KPI riches.
     */
    #[Route('/dashboard/admin', name: 'app_dashboard_admin', methods: ['GET'])]
    public function admin(
        RecommendationRepository $recoRepo,
        StructureRepository $structRepo,
        DepartmentRepository $deptRepo
    ): Response {
        $allRecos = $recoRepo->findAll();
        $counts = $recoRepo->countByStatus();

        // KPI clés
        $totalRecos = count($allRecos);
        $closedCount = $counts[Recommendation::STATUS_CLOSED] ?? 0;
        $inProgressCount = ($counts[Recommendation::STATUS_IN_PROGRESS] ?? 0)
            + ($counts[Recommendation::STATUS_SUBMITTED] ?? 0)
            + ($counts[Recommendation::STATUS_VALIDATED_CS] ?? 0);

        // Recos en retard : échéance dépassée ET pas encore clôturées
        $today = new \DateTimeImmutable('today');
        $overdueRecos = array_filter($allRecos, function ($r) use ($today) {
            if (!$r->getDueDate()) return false;
            if (in_array($r->getStatus(), [Recommendation::STATUS_CLOSED, Recommendation::STATUS_REJECTED], true)) return false;
            return $r->getDueDate() < $today;
        });

        // Recos urgentes (échéance < 7 jours, pas encore clôturées)
        $weekFromNow = $today->modify('+7 days');
        $upcomingRecos = array_filter($allRecos, function ($r) use ($today, $weekFromNow) {
            if (!$r->getDueDate()) return false;
            if (in_array($r->getStatus(), [Recommendation::STATUS_CLOSED, Recommendation::STATUS_REJECTED], true)) return false;
            return $r->getDueDate() >= $today && $r->getDueDate() <= $weekFromNow;
        });

        return $this->render('dashboard/admin.html.twig', [
            'counts' => $counts,
            'total_recos' => $totalRecos,
            'in_progress_count' => $inProgressCount,
            'overdue_count' => count($overdueRecos),
            'closed_count' => $closedCount,
            'total_structures' => $structRepo->count([]),
            'total_departments' => $deptRepo->count([]),
            'statuses' => Recommendation::STATUSES,
            'recent' => $recoRepo->findBy([], ['createdAt' => 'DESC'], 5),
            'overdue_recos' => array_slice(array_values($overdueRecos), 0, 3),
            'upcoming_recos' => array_slice(array_values($upcomingRecos), 0, 3),
        ]);
    }
    /**
     * Dashboard CHEF DE STRUCTURE — actions hiérarchiques.
     */
    #[Route('/dashboard/structure', name: 'app_dashboard_structure', methods: ['GET'])]
    public function structure(RecommendationRepository $recoRepo): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $structure = $user->getStructure();

        $recos = $structure ? $recoRepo->findByStructure($structure) : [];

        $toAffect = array_filter($recos, fn ($r) => $r->getStatus() === Recommendation::STATUS_VALIDATED);
        $toApprove = array_filter($recos, fn ($r) => $r->getStatus() === Recommendation::STATUS_VALIDATED_CS);
        $inProgress = array_filter($recos, fn ($r) => in_array($r->getStatus(), [
            Recommendation::STATUS_ASSIGNED,
            Recommendation::STATUS_IN_PROGRESS,
            Recommendation::STATUS_SUBMITTED,
        ], true));

        return $this->render('dashboard/structure.html.twig', [
            'structure' => $structure,
            'recos' => $recos,
            'to_affect' => $toAffect,
            'to_approve' => $toApprove,
            'in_progress' => $inProgress,
            'statuses' => Recommendation::STATUSES,
        ]);
    }

    /**
     * Dashboard CHEF DE SERVICE — actions sur son service.
     */
    #[Route('/dashboard/service', name: 'app_dashboard_service', methods: ['GET'])]
    public function service(RecommendationRepository $recoRepo): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $structure = $user->getStructure();

        $recos = $structure ? $recoRepo->findByStructure($structure) : [];

        $toAssign = array_filter($recos, fn ($r) => $r->getStatus() === Recommendation::STATUS_ASSIGNED);
        $toValidate = array_filter($recos, fn ($r) => $r->getStatus() === Recommendation::STATUS_SUBMITTED);
        $inProgress = array_filter($recos, fn ($r) => $r->getStatus() === Recommendation::STATUS_IN_PROGRESS);

        return $this->render('dashboard/service.html.twig', [
            'user' => $user,
            'structure' => $structure,
            'to_assign' => $toAssign,
            'to_validate' => $toValidate,
            'in_progress' => $inProgress,
            'statuses' => Recommendation::STATUSES,
        ]);
    }

    /**
     * Dashboard SECRÉTAIRE DE SÉANCE — création séances + saisie recommandations.
     */
    #[Route('/dashboard/secretary', name: 'app_dashboard_secretary', methods: ['GET'])]
    public function secretary(
        RecommendationRepository $recoRepo,
        MeetingRepository $meetingRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $upcomingMeetings = $meetingRepo->findBy(['status' => 'scheduled'], ['scheduledAt' => 'ASC'], 5);
        $totalMeetings = $meetingRepo->count([]);

        $draftRecos = $recoRepo->findByStatus(Recommendation::STATUS_DRAFT);
        $allRecos = $recoRepo->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('dashboard/secretary.html.twig', [
            'user' => $user,
            'upcoming_meetings' => $upcomingMeetings,
            'total_meetings' => $totalMeetings,
            'draft_recos' => $draftRecos,
            'recent_recos' => $allRecos,
        ]);
    }

    /**
     * Dashboard COORDONNATEUR — validation initiale + vue globale.
     */
    #[Route('/dashboard/coordinator', name: 'app_dashboard_coordinator', methods: ['GET'])]
    public function coordinator(
        RecommendationRepository $recoRepo,
        MeetingRepository $meetingRepo
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Recos en S0 : EN ATTENTE DE SA VALIDATION (son action principale)
        $toValidate = $recoRepo->findByStatus(Recommendation::STATUS_DRAFT);

        // Recos en S7 : approuvées, à suivre / clôturer
        $toFollowUp = $recoRepo->findByStatus(Recommendation::STATUS_APPROVED);

        // Vue d'ensemble globale
        $counts = $recoRepo->countByStatus();
        $totalRecos = count($recoRepo->findAll());
        $totalMeetings = $meetingRepo->count([]);

        return $this->render('dashboard/coordinator.html.twig', [
            'user' => $user,
            'to_validate' => $toValidate,
            'to_follow_up' => $toFollowUp,
            'counts' => $counts,
            'total_recos' => $totalRecos,
            'total_meetings' => $totalMeetings,
            'statuses' => Recommendation::STATUSES,
        ]);
    }
   /**
     * Dashboard AGENT — ses recommandations à traiter.
     */
    #[Route('/dashboard/agent', name: 'app_dashboard_agent', methods: ['GET'])]
    public function agent(RecommendationRepository $recoRepo): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Toutes les recos qui lui sont assignées
        $myRecos = $recoRepo->findByAssignedAgent($user);

        // Découpage par catégorie d'action
        $toStart = array_filter($myRecos, fn ($r) => $r->getStatus() === Recommendation::STATUS_IN_PROGRESS);
        $returned = array_filter($myRecos, fn ($r) => $r->getStatus() === Recommendation::STATUS_RETURNED);
        $submitted = array_filter($myRecos, fn ($r) => $r->getStatus() === Recommendation::STATUS_SUBMITTED);
        $validated = array_filter($myRecos, fn ($r) => in_array($r->getStatus(), [
            Recommendation::STATUS_VALIDATED_CS,
            Recommendation::STATUS_APPROVED,
            Recommendation::STATUS_CLOSED,
        ], true));

        return $this->render('dashboard/agent.html.twig', [
            'user' => $user,
            'my_recos' => $myRecos,
            'to_start' => $toStart,
            'returned' => $returned,
            'submitted' => $submitted,
            'validated' => $validated,
            'statuses' => Recommendation::STATUSES,
        ]);
    }

    /**
     * Dashboard STRUCTURE DE SUIVI — recommandations à clôturer.
     */
    #[Route('/dashboard/followup', name: 'app_dashboard_followup', methods: ['GET'])]
    public function followup(RecommendationRepository $recoRepo): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $toClose = $recoRepo->findByStatus(Recommendation::STATUS_APPROVED);
        $closed = $recoRepo->findByStatus(Recommendation::STATUS_CLOSED);

        return $this->render('dashboard/followup.html.twig', [
            'user' => $user,
            'to_close' => $toClose,
            'closed' => $closed,
            'statuses' => Recommendation::STATUSES,
        ]);
    }
}