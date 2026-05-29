<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Department;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Meeting;
use App\Entity\Recommendation;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // ============================================
        // Les 8 structures réelles de la CNPS
        // Chaque entrée : [code, libellé, type, email chef]
        // ============================================
        $structuresData = [
            ['DG',   'Direction Générale',                            Structure::TYPE_DIRECTION, 'dg@cnps.cm'],
            ['DRH',  'Direction des Ressources Humaines',             Structure::TYPE_DIRECTION, 'drh@cnps.cm'],
            ['DAF',  'Direction des Affaires Financières',            Structure::TYPE_DIRECTION, 'daf@cnps.cm'],
            ['DSI',  'Direction des Systèmes d\'Information',          Structure::TYPE_DIRECTION, 'dsi@cnps.cm'],
            ['DCAI', 'Direction du Contrôle et de l\'Audit Interne',   Structure::TYPE_DIRECTION, 'dcai@cnps.cm'],
            ['DPRC', 'Direction des Prestations et du Recouvrement',  Structure::TYPE_DIRECTION, 'dprc@cnps.cm'],
            ['DCQ',  'Direction du Contrôle et de la Qualité',        Structure::TYPE_DIRECTION, 'dcq@cnps.cm'],
            ['DCC',  'Direction de la Communication et de la Coopération', Structure::TYPE_DIRECTION, 'dcc@cnps.cm'],
        ];

        // On garde une référence aux structures créées,
        // pour pouvoir y rattacher des services ensuite.
        $structures = [];

        foreach ($structuresData as [$code, $label, $type, $email]) {
            $structure = new Structure();
            $structure->setCode($code)
                ->setLabel($label)
                ->setType($type)
                ->setChiefEmail($email)
                ->setActive(true);

            $manager->persist($structure);
            $structures[$code] = $structure; // on l'indexe par son code
        }

        // ============================================
        // Quelques services rattachés à leurs structures
        // [code service, libellé, code structure parente]
        // ============================================
        $departmentsData = [
            ['PAIE',    'Service Paie & Carrières',          'DRH'],
            ['RECRUT',  'Service Recrutement & Formation',   'DRH'],
            ['INFRA',   'Service Infrastructure & Réseaux',  'DSI'],
            ['DEV',     'Service Études & Développement',    'DSI'],
            ['COMPTA',  'Service Comptabilité',              'DAF'],
            ['BUDGET',  'Service Budget & Contrôle de gestion', 'DAF'],
            ['AUDIT',   'Service Audit Interne',             'DCAI'],
        ];

     $departments = []; // pour retrouver les services par code

        foreach ($departmentsData as [$code, $label, $parentCode]) {
            $department = new Department();
            $department->setCode($code)
                ->setLabel($label)
                ->setStructure($structures[$parentCode])
                ->setActive(true);

            $manager->persist($department);
            $departments[$code] = $department;
        }
        // ============================================
        // Agents de démonstration
        // [email, prénom, nom, matricule, rôle, code structure, code service]
        // ============================================
        $usersData = [
            ['admin@cnps.cm',       'Admin',   'SYSTÈME',    'CNPS-0001-A', User::ROLE_ADMIN,            'DSI',  null],
            ['j.mbarga@cnps.cm',    'Jean',    'MBARGA',     'CNPS-8842-X', User::ROLE_CHIEF_STRUCTURE,  'DSI',  null],
            ['p.mengue@cnps.cm',    'Paul',    'MENGUE',     'CNPS-7521-B', User::ROLE_CHIEF_SERVICE,    'DRH',  'PAIE'],
            ['e.ngobiyik@cnps.cm',  'Estelle', 'NGO BIYIK',  'CNPS-9023-C', User::ROLE_AGENT,            'DRH',  'PAIE'],
            ['a.etoa@cnps.cm',      'Alice',   'ETOA',       'CNPS-6610-D', User::ROLE_SECRETARY,        'DG',   null],
            ['s.abena@cnps.cm',     'Sylvie',  'ABENA',      'CNPS-5500-E', User::ROLE_COORDINATOR,      'DCAI', null],
            ['r.abomo@cnps.cm',     'Robert',  'ABOMO',      'CNPS-4412-F', User::ROLE_FOLLOWUP,         'DCAI', 'AUDIT'],
        ];

        foreach ($usersData as [$email, $firstName, $lastName, $matricule, $role, $structCode, $deptCode]) {
            $user = new User();
            $user->setEmail($email)
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setMatricule($matricule)
                ->setRoles([$role])
                ->setStructure($structures[$structCode])
                ->setActive(true)
                ->setAuthSource(User::AUTH_LOCAL);

            // Rattachement au service si précisé
            if ($deptCode !== null && isset($departments[$deptCode])) {
                $user->setDepartment($departments[$deptCode]);
            }

            // Mot de passe : tous "password" pour la démo
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

            $manager->persist($user);
        }
        // ============================================
        // Séances de démonstration
        // [titre, type, statut, jours depuis aujourd'hui, lieu]
        // ============================================
        $meetingsData = [
            ['Vérification de la conformité des primes 2026', Meeting::TYPE_AUDIT_INTERNE, Meeting::STATUS_SCHEDULED, 3, 'Salle des Actes'],
            ['Point hebdomadaire des activités de structure', Meeting::TYPE_REUNION_DIRECTION, Meeting::STATUS_SCHEDULED, 5, 'Visioconférence (Teams)'],
            ['Révision des procédures d\'archivage numérique', Meeting::TYPE_COMITE_TECHNIQUE, Meeting::STATUS_DRAFT, 12, 'Salle de réunion DSI'],
            ['Audit de sécurité du système de paie', Meeting::TYPE_AUDIT_INTERNE, Meeting::STATUS_DONE, -8, 'Salle des Actes'],
        ];
$meetings = []; // pour lier les recommandations à leur séance

        foreach ($meetingsData as $i => [$title, $type, $status, $daysOffset, $location]) {
            $meeting = new Meeting();
            $meeting->setTitle($title)
                ->setType($type)
                ->setStatus($status)
                ->setLocation($location)
                ->setScheduledAt(new \DateTimeImmutable(sprintf('%+d days', $daysOffset)));

            $manager->persist($meeting);
            $meetings[$i] = $meeting; // on garde par index (0, 1, 2, 3)
        }
        // ============================================
        // Recommandations de démonstration
        // Statuts variés pour illustrer le workflow.
        // [libellé, statut, priorité, jours échéance, index séance, code structure]
        // ============================================
        $recosData = [
            ['Mettre à jour la procédure de validation des primes 2026', Recommendation::STATUS_IN_PROGRESS, Recommendation::PRIORITY_HIGH, 15, 0, 'DRH'],
            ['Renforcer le contrôle d\'accès au système de paie', Recommendation::STATUS_APPROVED, Recommendation::PRIORITY_HIGH, 30, 3, 'DSI'],
            ['Archiver les dossiers physiques antérieurs à 2020', Recommendation::STATUS_ASSIGNED, Recommendation::PRIORITY_MEDIUM, 45, 2, 'DAF'],
            ['Former les agents au nouveau logiciel comptable', Recommendation::STATUS_SUBMITTED, Recommendation::PRIORITY_MEDIUM, 20, 1, 'DAF'],
            ['Réviser la politique de mots de passe interne', Recommendation::STATUS_VALIDATED, Recommendation::PRIORITY_LOW, 60, 3, 'DSI'],
            ['Clôturer l\'audit des fournisseurs 2025', Recommendation::STATUS_CLOSED, Recommendation::PRIORITY_MEDIUM, -5, 0, 'DCAI'],
            ['Corriger les écarts relevés sur les cotisations', Recommendation::STATUS_DRAFT, Recommendation::PRIORITY_HIGH, 25, null, null],
        ];

        foreach ($recosData as [$label, $status, $priority, $dueDays, $meetingIdx, $structCode]) {
            $reco = new Recommendation();
            $reco->setLabel($label)
                ->setStatus($status)
                ->setPriority($priority)
                ->setDueDate(new \DateTimeImmutable(sprintf('%+d days', $dueDays)));

            // Lien vers la séance d'origine (si défini)
            if ($meetingIdx !== null && isset($meetings[$meetingIdx])) {
                $reco->setMeeting($meetings[$meetingIdx]);
            }

            // Lien vers la structure affectée (si défini)
            if ($structCode !== null && isset($structures[$structCode])) {
                $reco->setAssignedStructure($structures[$structCode]);
            }

            $manager->persist($reco);
        }

        // On envoie tout en base d'un coup
        $manager->flush();
    }
}
