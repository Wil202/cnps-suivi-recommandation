<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Meeting;
use App\Form\MeetingType;
use App\Repository\MeetingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

class MeetingController extends AbstractController

{
    /**
     * Liste des séances — branchée sur la vraie base.
     */
    #[Route('/seances', name: 'app_meeting_index', methods: ['GET'])]
    public function index(MeetingRepository $meetingRepository): Response
    {
        // On récupère toutes les séances, les plus récentes d'abord
        $meetings = $meetingRepository->findBy([], ['createdAt' => 'DESC']);

        // Statistiques pour les cartes du bas
        $stats = [
            'monthly_total' => count($meetings),
            'next_audit' => [
                'title' => 'Prochain Audit Crucial',
                'description' => 'L\'audit de conformité réglementaire de la structure CNPS Zone Sud débutera dans moins de 48 heures.',
                'countdown' => '01j 14h 22m',
            ],
        ];

        return $this->render('meeting/index.html.twig', [
            'meetings' => $meetings,
            'stats' => $stats,
        ]);
    }

    /**
     * Créer une nouvelle séance.
     */
    #[Route('/seances/new', name: 'app_meeting_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $meeting = new Meeting();
        $form = $this->createForm(MeetingType::class, $meeting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($meeting);
            $entityManager->flush();

            $this->addFlash('success', 'La séance « ' . $meeting->getTitle() . ' » a été créée.');

            return $this->redirectToRoute('app_meeting_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('meeting/new.html.twig', [
            'meeting' => $meeting,
            'form' => $form,
        ]);
    }

    /**
     * Modifier une séance.
     */
    #[Route('/seances/{id}/edit', name: 'app_meeting_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Meeting $meeting, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MeetingType::class, $meeting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La séance a été modifiée.');

            return $this->redirectToRoute('app_meeting_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('meeting/edit.html.twig', [
            'meeting' => $meeting,
            'form' => $form,
        ]);
    }

    /**
     * Supprimer une séance.
     */
    #[Route('/seances/{id}', name: 'app_meeting_delete', methods: ['POST'])]
    public function delete(Request $request, Meeting $meeting, EntityManagerInterface $entityManager): Response
    {
        // Vérification du jeton CSRF (sécurité anti-falsification)
        if ($this->isCsrfTokenValid('delete' . $meeting->getId(), $request->request->get('_token'))) {
            $entityManager->remove($meeting);
            $entityManager->flush();
            $this->addFlash('success', 'La séance a été supprimée.');
        }

        return $this->redirectToRoute('app_meeting_index', [], Response::HTTP_SEE_OTHER);
    }
}
