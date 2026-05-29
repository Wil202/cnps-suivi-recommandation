<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur admin de démo',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si l'admin existe déjà
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@cnps.cm']);
        if ($existing) {
            $io->warning('Un utilisateur admin@cnps.cm existe déjà. Suppression...');
            $this->em->remove($existing);
            $this->em->flush();
        }

        // Créer l'admin
        $admin = new User();
        $admin->setEmail('admin@cnps.cm');
        $admin->setFirstName('Jean');
        $admin->setLastName('MBARGA');
        $admin->setMatricule('CNPS-8842-X');
        $admin->setRoles([User::ROLE_CHIEF_STRUCTURE, User::ROLE_ADMIN]);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin'));

        $this->em->persist($admin);
        $this->em->flush();

        $io->success([
            'Admin créé avec succès !',
            'Email    : admin@cnps.cm',
            'Mot de passe : admin',
            'Rôle     : Chef de structure + Admin',
        ]);

        return Command::SUCCESS;
    }
}
