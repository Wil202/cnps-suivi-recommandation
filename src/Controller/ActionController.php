<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ActionController extends AbstractController
{
    #[Route('/recommandations/{code}/saisir-action', name: 'app_action_new')]
    public function new(string $code = 'RECO-2023-0892'): Response
    {
        // ============================================
        // DONNÉES FICTIVES — Sprint 2 : à brancher BDD
        // ============================================
        $recommendation = [
            'code' => $code,
            'priority' => 'HAUTE',
            'priority_color' => 'high',
            'title' => 'Mise en conformité des systèmes d\'archivage numérique',
            'description' => 'Cette recommandation fait suite à l\'audit de sécurité du T3. L\'objectif est de garantir l\'intégrité et la pérennité des documents administratifs scannés selon la norme ISO 15489.',
            'deadline' => '15 Octobre 2023',
            'responsible' => 'Jean Dupont (Chef de Service)',
        ];

        $history = [
            [
                'date' => 'Hier, 14:30',
                'title' => 'Audit des serveurs actuels',
                'description' => 'Inventaire complet effectué sur le site de Cocody. Identification de 3 serveurs obsolètes.',
                'attachment' => 'rapport_audit_v1.pdf',
                'status' => 'completed',
            ],
            [
                'date' => '05 Sept. 2023',
                'title' => 'Réunion de cadrage technique',
                'description' => 'Définition des besoins de stockage et du calendrier de mise en œuvre avec l\'équipe IT.',
                'attachment' => null,
                'status' => 'completed',
            ],
            [
                'date' => '01 Sept. 2023',
                'title' => 'Assignation de la recommandation',
                'description' => 'Prise en charge du dossier par l\'Agent de Maintenance.',
                'attachment' => null,
                'status' => 'completed',
            ],
        ];

        return $this->render('action/new.html.twig', [
            'reco' => $recommendation,
            'history' => $history,
        ]);
    }
}
