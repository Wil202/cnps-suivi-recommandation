<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ValidationController extends AbstractController
{
    /**
     * Page de validation/approbation des recommandations.
     *
     * Sert pour 3 rôles distincts :
     *  - Chef de service       → valide les actions soumises par ses agents (S4 → S5)
     *  - Chef de structure     → approuve les dossiers validés par CS (S5 → S7)
     *  - Structure de suivi    → clôture les dossiers approuvés (S7 → S9)
     *
     * Le paramètre 'role' (en query string) simule le rôle de l'utilisateur connecté.
     * En Phase 2, on utilisera $this->getUser()->getRoles().
     */
    #[Route('/validation', name: 'app_validation_index')]
    public function index(Request $request): Response
    {
        // Récupération du rôle actif (par défaut : chef de service)
        // Exemple d'URL : /validation?role=chief_structure
        $role = $request->query->get('role', 'chief_service');

        // ============================================
        // Données fictives selon le rôle
        // ============================================
        $rolesData = $this->getDataByRole($role);

        // ID de la reco sélectionnée (par défaut : la première de la file)
        $selectedId = (int) $request->query->get('reco', $rolesData['queue'][0]['id'] ?? 0);

        // On cherche la reco sélectionnée dans la file
        $selectedReco = null;
        foreach ($rolesData['queue'] as $reco) {
            if ($reco['id'] === $selectedId) {
                $selectedReco = $reco;
                break;
            }
        }

        return $this->render('validation/index.html.twig', [
            'currentRole' => $role,
            'roleData' => $rolesData,
            'selectedReco' => $selectedReco,
        ]);
    }

    /**
     * Retourne les données et configuration selon le rôle.
     * Cette méthode privée centralise toute la logique métier
     * (que faire valider, quels boutons afficher, etc.).
     */
    private function getDataByRole(string $role): array
    {
        $configs = [
            // ============================================
            // CHEF DE SERVICE
            // ============================================
            'chief_service' => [
                'role_label' => 'Chef de service',
                'role_icon' => 'bi-person-badge',
                'role_color' => '#1F4E79',
                'queue_title' => 'Recommandations soumises par mes agents',
                'queue_subtitle' => 'Validez les actions saisies avant transmission au Chef de structure',
                'expected_status' => 'S4 — Soumise',
                'next_status' => 'S5 — Validée CS',
                'actions' => [
                    ['key' => 'validate', 'label' => 'Valider et transmettre', 'icon' => 'bi-check-circle-fill', 'color' => 'success', 'next_status' => 'S5'],
                    ['key' => 'return', 'label' => 'Renvoyer à l\'agent', 'icon' => 'bi-arrow-counterclockwise', 'color' => 'warning text-white', 'next_status' => 'S6'],
                    ['key' => 'modify', 'label' => 'Modifier les actions', 'icon' => 'bi-pencil-square', 'color' => 'outline-secondary'],
                ],
                'queue' => [
                    [
                        'id' => 1,
                        'code' => 'CNPS-REC-2026-00142',
                        'title' => 'Mise à niveau du parc serveurs Datacenter Yaoundé',
                        'agent' => 'M. KAMGA',
                        'agent_initials' => 'MK',
                        'submitted_date' => '2 mai 2026',
                        'submitted_relative' => 'il y a 2 jours',
                        'actions_count' => 4,
                        'attachments_count' => 7,
                        'urgency' => 'normal',
                        'preview' => 'Migration complète des 8 serveurs HP Proliant vers la nouvelle infrastructure Dell PowerEdge. Tests de bascule réussis.',
                    ],
                    [
                        'id' => 2,
                        'code' => 'CNPS-REC-2026-00141',
                        'title' => 'Refonte du processus de remboursement prestations',
                        'agent' => 'Mme NGUEMA',
                        'agent_initials' => 'AN',
                        'submitted_date' => '4 mai 2026',
                        'submitted_relative' => 'hier',
                        'actions_count' => 6,
                        'attachments_count' => 12,
                        'urgency' => 'high',
                        'preview' => 'Cartographie complète du processus actuel, identification de 14 points de friction. Proposition de nouveau workflow validée en COPIL.',
                    ],
                    [
                        'id' => 3,
                        'code' => 'CNPS-REC-2026-00137',
                        'title' => 'Sécurisation des accès au système de paiement',
                        'agent' => 'M. TAGNE',
                        'agent_initials' => 'CT',
                        'submitted_date' => '5 mai 2026',
                        'submitted_relative' => 'aujourd\'hui',
                        'actions_count' => 3,
                        'attachments_count' => 5,
                        'urgency' => 'normal',
                        'preview' => 'Mise en place de l\'authentification à deux facteurs sur l\'application de paie. Audit de sécurité réalisé par un cabinet externe.',
                    ],
                ],
            ],

            // ============================================
            // CHEF DE STRUCTURE
            // ============================================
            'chief_structure' => [
                'role_label' => 'Chef de structure',
                'role_icon' => 'bi-buildings',
                'role_color' => '#2E75B6',
                'queue_title' => 'Dossiers validés en attente d\'approbation',
                'queue_subtitle' => 'Approuvez les dossiers validés par vos chefs de service',
                'expected_status' => 'S5 — Validée CS',
                'next_status' => 'S7 — Approuvée',
                'actions' => [
                    ['key' => 'approve', 'label' => 'APPROUVER (visibilité inter-structures)', 'icon' => 'bi-shield-check', 'color' => 'success', 'next_status' => 'S7'],
                    ['key' => 'reject', 'label' => 'Rejeter avec orientations', 'icon' => 'bi-x-circle', 'color' => 'danger', 'next_status' => 'S6'],
                    ['key' => 'comment', 'label' => 'Demander précisions', 'icon' => 'bi-chat-left-text', 'color' => 'outline-secondary'],
                ],
                'queue' => [
                    [
                        'id' => 4,
                        'code' => 'CNPS-REC-2026-00133',
                        'title' => 'Mise en place d\'un plan de continuité d\'activité',
                        'agent' => 'M. MBALLA',
                        'agent_initials' => 'JM',
                        'cs_validator' => 'M. P. MENGUE',
                        'submitted_date' => '3 mai 2026',
                        'submitted_relative' => 'il y a 1 jour',
                        'actions_count' => 8,
                        'attachments_count' => 15,
                        'urgency' => 'normal',
                        'preview' => 'PCA complet rédigé conforme à la norme ISO 22301. Tests de continuité prévus en juin. Validation CS effectuée le 03/05/2026.',
                    ],
                    [
                        'id' => 5,
                        'code' => 'CNPS-REC-2026-00128',
                        'title' => 'Optimisation du circuit de validation des congés',
                        'agent' => 'Mme ATEBA',
                        'agent_initials' => 'EA',
                        'cs_validator' => 'M. S. ESSOMBA',
                        'submitted_date' => '5 mai 2026',
                        'submitted_relative' => 'aujourd\'hui',
                        'actions_count' => 5,
                        'attachments_count' => 8,
                        'urgency' => 'high',
                        'preview' => 'Nouveau workflow dématérialisé via l\'intranet. Formation des managers planifiée. Économie estimée : 12 jours-homme/mois.',
                    ],
                ],
            ],

            // ============================================
            // STRUCTURE DE SUIVI
            // ============================================
            'followup' => [
                'role_label' => 'Structure de suivi',
                'role_icon' => 'bi-eye-fill',
                'role_color' => '#14B8A6',
                'queue_title' => 'Dossiers approuvés en attente de clôture',
                'queue_subtitle' => 'Vérifiez la mise en œuvre effective avant clôture définitive',
                'expected_status' => 'S7 — Approuvée',
                'next_status' => 'S9 — Clôturée',
                'actions' => [
                    ['key' => 'close', 'label' => 'CLÔTURER définitivement', 'icon' => 'bi-check2-all', 'color' => 'success', 'next_status' => 'S9'],
                    ['key' => 'reject_close', 'label' => 'Rejeter la clôture', 'icon' => 'bi-x-octagon', 'color' => 'danger', 'next_status' => 'S10'],
                    ['key' => 'inspect', 'label' => 'Demander un contrôle terrain', 'icon' => 'bi-search', 'color' => 'outline-secondary'],
                ],
                'queue' => [
                    [
                        'id' => 6,
                        'code' => 'CNPS-REC-2026-00130',
                        'title' => 'Renforcement du dispositif anti-fraude',
                        'agent' => 'M. ABOMO',
                        'agent_initials' => 'RA',
                        'submitted_date' => '1 mai 2026',
                        'submitted_relative' => 'il y a 4 jours',
                        'actions_count' => 9,
                        'attachments_count' => 18,
                        'urgency' => 'normal',
                        'preview' => 'Mise en production du nouveau module de détection. 23 fraudes identifiées le 1er mois. ROI confirmé. Dossier complet pour clôture.',
                    ],
                ],
            ],
        ];

        return $configs[$role] ?? $configs['chief_service'];
    }
}
