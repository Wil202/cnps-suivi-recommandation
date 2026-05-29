<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account')]
    public function index(): Response
    {
        // ============================================
        // DONNÉES FICTIVES — Sprint Auth : à brancher
        // ============================================
        $user = [
            'first_name' => 'Jean',
            'last_name' => 'MBARGA',
            'initials' => 'JM',
            'role' => 'CHEF DE STRUCTURE · DSI',
            'matricule' => 'CNPS-8842-X',
            'structure' => 'Direction des Systèmes d\'Information',
            'department' => 'Division de l\'Infrastructure',
            'email' => 'j.mbarga@cnps.cm',
            'preferences' => [
                'email_notifications' => true,
                'mobile_alerts' => true,
                'biometric' => false,
                'dark_mode' => false,
                'language' => 'Français (FR)',
            ],
            'app_version' => '2.4.1 (Build 890)',
        ];

        return $this->render('account/index.html.twig', [
            'user' => $user,
        ]);
    }
}