<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \Stringable
{
    // Rôles applicatifs CNPS
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_AGENT = 'ROLE_AGENT';
    public const ROLE_CHIEF_SERVICE = 'ROLE_CHIEF_SERVICE';
    public const ROLE_CHIEF_STRUCTURE = 'ROLE_CHIEF_STRUCTURE';
    public const ROLE_COORDINATOR = 'ROLE_COORDINATOR';
    public const ROLE_SECRETARY = 'ROLE_SECRETARY';
    public const ROLE_FOLLOWUP = 'ROLE_FOLLOWUP';
    public const ROLE_DG = 'ROLE_DG';

    public const ROLES_LABELS = [
        self::ROLE_ADMIN => 'Administrateur',
        self::ROLE_AGENT => 'Agent',
        self::ROLE_CHIEF_SERVICE => 'Chef de service',
        self::ROLE_CHIEF_STRUCTURE => 'Chef de structure',
        self::ROLE_COORDINATOR => 'Coordonnateur Comité projets',
        self::ROLE_SECRETARY => 'Secrétaire de séance',
        self::ROLE_FOLLOWUP => 'Structure de suivi',
        self::ROLE_DG => 'Direction Générale',
    ];
    // Sources d'authentification
    public const AUTH_LOCAL = 'local';
    public const AUTH_LDAP = 'ldap';    

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'email n\'est pas valide.')]
    private ?string $email = null;

    /**
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $matricule = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;


    /**
     * Source d'authentification : 'local' (mot de passe en base) ou 'ldap' (Active Directory).
     * Permet de faire cohabiter les deux modes de connexion.
     */
    #[ORM\Column(length: 10)]
    private string $authSource = self::AUTH_LOCAL;

    #[ORM\ManyToOne(targetEntity: Structure::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Structure $structure = null;

    #[ORM\ManyToOne(targetEntity: Department::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Department $department = null;

    public function __construct()
    {
        $this->active = true;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Tout utilisateur a au minimum ROLE_USER
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Pour stocker des données sensibles temporaires, on les nettoie ici
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = trim($firstName);
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = strtoupper(trim($lastName));
        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): static
    {
        $this->matricule = $matricule;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    // Méthodes utilitaires
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getInitials(): string
    {
        $first = mb_substr($this->firstName ?? '', 0, 1);
        $last = mb_substr($this->lastName ?? '', 0, 1);
        return mb_strtoupper($first . $last);
    }

    public function getMainRole(): string
    {
        foreach ($this->roles as $role) {
            if (isset(self::ROLES_LABELS[$role])) {
                return $role;
            }
        }
        return 'ROLE_USER';
    }

    public function getMainRoleLabel(): string
    {
        return self::ROLES_LABELS[$this->getMainRole()] ?? 'Utilisateur';
    }

    public function getAuthSource(): string
    {
        return $this->authSource;
    }

    public function setAuthSource(string $authSource): static
    {
        $this->authSource = $authSource;
        return $this;
    }

    public function isLdapUser(): bool
    {
        return $this->authSource === self::AUTH_LDAP;
    }

    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    public function setStructure(?Structure $structure): static
    {
        $this->structure = $structure;
        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): static
    {
        $this->department = $department;
        return $this;
    }

     public function __toString(): string
    {
        return $this->getFullName() ?: ($this->email ?? '???');
    }
}
