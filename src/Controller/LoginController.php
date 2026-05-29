<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    /**
     * Affiche la page de login.
     *
     * Pour l'instant : pas d'authentification réelle.
     * Le formulaire redirige simplement vers le dashboard.
     */
    #[Route('/login', name: 'app_login')]
    public function index(Request $request): Response
    {
        // Si l'utilisateur soumet le formulaire (méthode POST),
        // on le redirige vers le dashboard (simulation de connexion réussie)
        if ($request->isMethod('POST')) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Sinon (méthode GET = simple affichage), on affiche la page de login
        return $this->render('login/index.html.twig');
    }
}
