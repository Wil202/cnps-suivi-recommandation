<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Recommendation;
use App\Form\RecommendationType;
use App\Repository\EventRepository;
use App\Repository\RecommendationRepository;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\WorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RecommendationController extends AbstractController
{
    /**
     * Liste des recommandations — branchée sur la vraie base.
     */
    #[Route('/recommandations', name: 'app_recommendation_index', methods: ['GET'])]
    public function index(RecommendationRepository $recommendationRepository): Response
    {
        $recommendations = $recommendationRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('recommendation/index.html.twig', [
            'recommendations' => $recommendations,
            'statuses' => Recommendation::STATUSES,
        ]);
    }

    /**
     * Créer une nouvelle recommandation.
     */
    #[Route('/recommandations/new', name: 'app_recommendation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recommendation = new Recommendation();
        $form = $this->createForm(RecommendationType::class, $recommendation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recommendation);
            $entityManager->flush();

            $this->addFlash('success', 'La recommandation « ' . $recommendation->getLabel() . ' » a été créée.');

            return $this->redirectToRoute('app_recommendation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recommendation/new.html.twig', [
            'recommendation' => $recommendation,
            'form' => $form,
        ]);
    }

    /**
     * Affecter une recommandation à un agent (S2 → S3).
     * Réservé au chef de service de la structure d'exécution (RG-12).
     */
    #[Route('/recommandations/{reference}/affecter-agent', name: 'app_recommendation_assign_agent', methods: ['GET', 'POST'])]
    public function assignAgent(
        string $reference,
        Request $request,
        RecommendationRepository $recoRepo,
        UserRepository $userRepo,
        \App\Service\WorkflowService $workflow,
        EntityManagerInterface $em
    ): Response {
        $reco = $recoRepo->findOneBy(['reference' => $reference]);
        if (!$reco) {
            throw $this->createNotFoundException('Recommandation introuvable.');
        }

        // Sécurité : seul un chef de service peut le faire
        if (!$this->isGranted('ROLE_CHIEF_SERVICE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Seul un chef de service peut affecter à un agent.');
        }

        // Le statut doit être S2 (Affectée à la structure)
        if ($reco->getStatus() !== \App\Entity\Recommendation::STATUS_ASSIGNED) {
            $this->addFlash('warning', 'Cette recommandation ne peut être affectée à un agent que depuis le statut S2 (Affectée).');
            return $this->redirectToRoute('app_recommendation_show', ['reference' => $reference]);
        }

        // Agents disponibles : on prend tous les agents de la même structure
        $structure = $reco->getAssignedStructure();
        $availableAgents = $userRepo->findBy([
            'structure' => $structure,
        ]);
        // On filtre côté PHP pour ne garder que ceux qui ont ROLE_AGENT
        $availableAgents = array_filter($availableAgents, fn ($u) => in_array('ROLE_AGENT', $u->getRoles(), true));

        if ($request->isMethod('POST')) {
            $agentId = (int) $request->request->get('agent_id');
            $comment = trim((string) $request->request->get('comment', ''));

            $agent = $userRepo->find($agentId);
            if (!$agent || !in_array('ROLE_AGENT', $agent->getRoles(), true)) {
                $this->addFlash('danger', 'Agent invalide.');
                return $this->redirectToRoute('app_recommendation_assign_agent', ['reference' => $reference]);
            }

            // 1. Affecter l'agent
            $reco->setAssignedAgent($agent);

            // 2. Transition de statut S2 → S3 via le WorkflowService (qui trace l'événement RG-07)
            $workflow->transition(
                $reco,
                \App\Entity\Recommendation::STATUS_IN_PROGRESS,
                $this->getUser(),
                $comment !== '' ? $comment : sprintf('Affecté à %s', $agent->getFullName())
            );

            $em->flush();

            $this->addFlash('success', sprintf('Recommandation affectée à %s.', $agent->getFullName()));
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('recommendation/assign_agent.html.twig', [
            'reco' => $reco,
            'agents' => array_values($availableAgents),
        ]);
    }

    /**
     * Détail d'une recommandation — par sa référence (le "code").
     */
    #[Route('/recommandations/{reference}', name: 'app_recommendation_show', methods: ['GET'])]
    public function show(
        string $reference,
        RecommendationRepository $recommendationRepository,
        WorkflowService $workflowService,
        EventRepository $eventRepository
    ): Response {
        $recommendation = $recommendationRepository->findOneBy(['reference' => $reference]);

        if (!$recommendation) {
            throw $this->createNotFoundException('Recommandation introuvable : ' . $reference);
        }

        return $this->render('recommendation/show.html.twig', [
            'recommendation' => $recommendation,
            'statuses' => Recommendation::STATUSES,
            'available_actions' => $workflowService->getAvailableActions($recommendation, $this->getUser()),
            'history' => $eventRepository->findByRecommendation($recommendation),
        ]);
    }

    /**
     * Modifier une recommandation.
     */
    #[Route('/recommandations/{id}/edit', name: 'app_recommendation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Recommendation $recommendation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecommendationType::class, $recommendation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La recommandation a été modifiée.');

            return $this->redirectToRoute('app_recommendation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recommendation/edit.html.twig', [
            'recommendation' => $recommendation,
            'form' => $form,
        ]);
    }

    /**
     * Fait avancer une recommandation vers un nouveau statut du workflow.
     * La transition n'est appliquée que si elle est autorisée par le WorkflowService.
     */
    #[Route('/recommandations/{id}/transition', name: 'app_recommendation_transition', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transition(
        Request $request,
        Recommendation $recommendation,
        WorkflowService $workflowService,
        EntityManagerInterface $entityManager
    ): Response {
        $targetStatus = $request->request->get('target_status');

        // Vérification du jeton CSRF (sécurité)
        if (!$this->isCsrfTokenValid('transition' . $recommendation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_recommendation_show', ['reference' => $recommendation->getReference()]);
        }

        // On passe l'utilisateur connecté comme auteur de la transition
        $comment = $request->request->get('comment'); // motif optionnel
        // Vérification métier : l'utilisateur a-t-il le droit de faire cette transition selon son rôle ?
        if (!$workflowService->canUserPerformTransition($recommendation, $targetStatus, $this->getUser())) {
            $this->addFlash('error', 'Vous n\'avez pas le droit d\'effectuer cette transition selon votre rôle.');
            return $this->redirectToRoute('app_recommendation_show', ['reference' => $recommendation->getReference()]);
        }
        if ($workflowService->applyTransition($recommendation, $targetStatus, $this->getUser(), $comment)) {
            $entityManager->flush(); // on enregistre le nouveau statut + l'événement
            $this->addFlash('success', 'Recommandation passée au statut « ' . $recommendation->getStatusLabel() . ' ».');
        } else {
            $this->addFlash('error', 'Cette transition n\'est pas autorisée depuis le statut actuel.');
        }

        return $this->redirectToRoute('app_recommendation_show', ['reference' => $recommendation->getReference()]);
    }

    /**
     * Supprimer une recommandation.
     */
    #[Route('/recommandations/{id}', name: 'app_recommendation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Recommendation $recommendation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $recommendation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($recommendation);
            $entityManager->flush();
            $this->addFlash('success', 'La recommandation a été supprimée.');
        }

        return $this->redirectToRoute('app_recommendation_index', [], Response::HTTP_SEE_OTHER);
    }
}