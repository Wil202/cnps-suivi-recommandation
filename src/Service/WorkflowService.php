<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Event;
use App\Entity\Recommendation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service métier qui pilote le workflow d'une recommandation (S0 → S10).
 *
 * Implémente la matrice des droits du cahier des charges section 7.5 :
 * chaque rôle ne peut déclencher que les transitions qui le concernent.
 */
class WorkflowService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Transitions autorisées par la machine à états (indépendamment du rôle).
     * Clé = statut de départ, valeur = liste des statuts d'arrivée possibles.
     */
    private const TRANSITIONS = [
        Recommendation::STATUS_DRAFT         => [Recommendation::STATUS_VALIDATED, Recommendation::STATUS_REJECTED],
        Recommendation::STATUS_VALIDATED     => [Recommendation::STATUS_ASSIGNED],
        Recommendation::STATUS_ASSIGNED      => [Recommendation::STATUS_IN_PROGRESS],
        Recommendation::STATUS_IN_PROGRESS   => [Recommendation::STATUS_SUBMITTED],
        Recommendation::STATUS_SUBMITTED     => [Recommendation::STATUS_VALIDATED_CS, Recommendation::STATUS_RETURNED],
        Recommendation::STATUS_VALIDATED_CS  => [Recommendation::STATUS_APPROVED, Recommendation::STATUS_RETURNED],
        Recommendation::STATUS_RETURNED      => [Recommendation::STATUS_IN_PROGRESS],
        Recommendation::STATUS_APPROVED      => [Recommendation::STATUS_CLOSED, Recommendation::STATUS_REJECTED],
        Recommendation::STATUS_RELAUNCHED    => [Recommendation::STATUS_SUBMITTED],
        Recommendation::STATUS_CLOSED        => [],
        Recommendation::STATUS_REJECTED      => [Recommendation::STATUS_IN_PROGRESS],
    ];

    /**
     * Libellés d'action pour chaque transition (texte affiché sur le bouton).
     * Clé = "STATUT_DEPART>STATUT_ARRIVEE"
     */
    private const TRANSITION_LABELS = [
        'S0>S1'  => 'Valider le projet',
        'S1>S2'  => 'Affecter à une structure',
        'S2>S3'  => 'Démarrer le traitement',
        'S3>S4'  => 'Soumettre au chef de service',
        'S4>S5'  => 'Valider (chef de service)',
        'S4>S6'  => 'Renvoyer pour correction',
        'S5>S7'  => 'Approuver (chef de structure)',
        'S5>S6'  => 'Renvoyer pour correction',
        'S6>S3'  => 'Reprendre le traitement',
        'S7>S9'  => 'Clôturer',
        'S7>S10' => 'Rejeter la clôture',
        'S8>S4'  => 'Re-soumettre',
        'S10>S3' => 'Reprendre le traitement',
    ];

    /**
     * Retourne la liste des statuts vers lesquels la recommandation peut transiter.
     *
     * @return string[] codes de statut (ex: ['S5', 'S6'])
     */
    public function getAvailableTransitions(Recommendation $recommendation): array
    {
        return self::TRANSITIONS[$recommendation->getStatus()] ?? [];
    }

    /**
     * Vérifie si une transition donnée est autorisée depuis le statut actuel.
     */
    public function canTransition(Recommendation $recommendation, string $targetStatus): bool
    {
        return in_array($targetStatus, $this->getAvailableTransitions($recommendation), true);
    }

    /**
     * Vérifie si un UTILISATEUR peut effectivement déclencher une transition,
     * selon son rôle et la matrice des droits (cahier des charges 7.5).
     */
    public function canUserPerformTransition(
        Recommendation $recommendation,
        string $targetStatus,
        ?User $user
    ): bool {
        if ($user === null) {
            return false;
        }

        // L'admin a tous les droits (super-utilisateur)
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->canTransition($recommendation, $targetStatus);
        }

        // La transition doit d'abord être autorisée par la machine à états
        if (!$this->canTransition($recommendation, $targetStatus)) {
            return false;
        }

        $from = $recommendation->getStatus();
        $key = $from . '>' . $targetStatus;
        $roles = $user->getRoles();

        // Matrice des droits par transition (cahier des charges 7.5)
        $rules = [
            'S0>S1'  => ['ROLE_COORDINATOR'],
            'S1>S2'  => ['ROLE_CHIEF_STRUCTURE'],
            'S2>S3'  => ['ROLE_CHIEF_SERVICE'],
            'S4>S5'  => ['ROLE_CHIEF_SERVICE'],
            'S4>S6'  => ['ROLE_CHIEF_SERVICE'],
            'S5>S7'  => ['ROLE_CHIEF_STRUCTURE'],
            'S5>S6'  => ['ROLE_CHIEF_STRUCTURE'],
            'S7>S9'  => ['ROLE_FOLLOWUP'],
            'S7>S10' => ['ROLE_FOLLOWUP'],
            'S8>S4'  => ['ROLE_AGENT'],
            'S10>S3' => ['ROLE_CHIEF_SERVICE'],
        ];

        // Règles particulières : l'agent ne peut agir que sur SES propres recos
        $agentOnlyOwnReco = ['S3>S4', 'S6>S3'];

        if (in_array($key, $agentOnlyOwnReco, true)) {
            if (!in_array('ROLE_AGENT', $roles, true)) {
                return false;
            }
            $assignedAgent = $recommendation->getAssignedAgent();
            return $assignedAgent && $assignedAgent->getId() === $user->getId();
        }

        // Règle générale : vérifier que l'utilisateur a un rôle autorisé
        if (!isset($rules[$key])) {
            return false;
        }

        foreach ($rules[$key] as $allowedRole) {
            if (in_array($allowedRole, $roles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Effectue la transition si elle est autorisée, et trace l'événement.
     */
    public function applyTransition(
        Recommendation $recommendation,
        string $targetStatus,
        ?User $author = null,
        ?string $comment = null
    ): bool {
        if (!$this->canTransition($recommendation, $targetStatus)) {
            return false;
        }

        // On mémorise le statut de départ AVANT de le changer
        $fromStatus = $recommendation->getStatus();

        // On applique le nouveau statut
        $recommendation->setStatus($targetStatus);

        // On trace l'événement (RG-07 : chaque transition est historisée)
        $event = new Event();
        $event->setRecommendation($recommendation)
            ->setFromStatus($fromStatus)
            ->setToStatus($targetStatus)
            ->setAuthor($author)
            ->setComment($comment);

        // On persiste l'événement (le flush sera fait par le contrôleur)
        $this->entityManager->persist($event);

        return true;
    }

    /**
     * Construit la liste des actions possibles pour l'affichage des boutons,
     * filtrée selon le rôle de l'utilisateur (s'il est fourni).
     *
     * @return array<array{target: string, label: string}>
     */
    public function getAvailableActions(Recommendation $recommendation, ?User $user = null): array
    {
        $actions = [];
        $from = $recommendation->getStatus();

        foreach ($this->getAvailableTransitions($recommendation) as $target) {
            // Filtrer par rôle si un utilisateur est fourni
            if ($user !== null && !$this->canUserPerformTransition($recommendation, $target, $user)) {
                continue;
            }
            $key = $from . '>' . $target;
            $actions[] = [
                'target' => $target,
                'label' => self::TRANSITION_LABELS[$key] ?? ('Passer à ' . $target),
            ];
        }

        return $actions;
    }
}