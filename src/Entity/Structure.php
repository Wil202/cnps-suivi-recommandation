<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StructureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StructureRepository::class)]
#[ORM\Table(name: 'structures')]
#[ORM\Index(columns: ['code'], name: 'idx_structure_code')]
#[ORM\Index(columns: ['type'], name: 'idx_structure_type')]
#[ORM\Index(columns: ['active'], name: 'idx_structure_active')]
#[ORM\HasLifecycleCallbacks]
class Structure implements \Stringable
{
    // ============================================================
    // CONSTANTES — Types de structures (référence métier CNPS)
    // ============================================================

    public const TYPE_DIRECTION = 'DIRECTION';
    public const TYPE_SUBDIVISION = 'SUBDIVISION';
    public const TYPE_CELL = 'CELL';
    public const TYPE_COMMITTEE = 'COMMITTEE';

    /**
     * Libellés humains pour l'affichage dans les formulaires.
     */
    public const TYPES = [
        self::TYPE_DIRECTION => 'Direction',
        self::TYPE_SUBDIVISION => 'Sous-direction',
        self::TYPE_CELL => 'Cellule',
        self::TYPE_COMMITTEE => 'Comité',
    ];

    // ============================================================
    // PROPRIÉTÉS
    // ============================================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code institutionnel unique (ex: DRH, DSI, DCAI).
     */
    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(message: 'Le code de la structure est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 20,
        minMessage: 'Le code doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le code ne peut excéder {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9_-]+$/',
        message: 'Le code ne peut contenir que des lettres majuscules, chiffres, tirets et underscores.'
    )]
    private ?string $code = null;

    /**
     * Libellé complet (ex: "Direction des Ressources Humaines").
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le libellé de la structure est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le libellé doit contenir au moins {{ limit }} caractères.'
    )]
    private ?string $label = null;

    /**
     * Type de structure : Direction, Sous-direction, Cellule, Comité.
     * Voir les constantes TYPE_* ci-dessus.
     */
    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de structure est obligatoire.')]
    #[Assert\Choice(
        choices: [self::TYPE_DIRECTION, self::TYPE_SUBDIVISION, self::TYPE_CELL, self::TYPE_COMMITTEE],
        message: 'Le type de structure n\'est pas valide.'
    )]
    private ?string $type = null;

    /**
     * Email du chef de structure (facultatif — l'email officiel sera plutôt
     * porté par l'utilisateur via la relation chiefStructure).
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email(message: 'L\'email du chef n\'est pas valide.')]
    #[Assert\Length(max: 255)]
    private ?string $chiefEmail = null;

    /**
     * Description courte de la mission de la structure (optionnel).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    /**
     * Statut actif/inactif (archivage logique sans suppression).
     */
    #[ORM\Column]
    private bool $active = true;

    /**
     * Date de création (auto-renseignée).
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière modification (auto-renseignée).
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Department>
     */
    #[ORM\OneToMany(targetEntity: Department::class, mappedBy: 'structure', orphanRemoval: true)]
    private Collection $departments;

    // ============================================================
    // RELATIONS — pour l'instant en commentaire
    // Elles seront décommentées une fois les autres entités créées
    // ============================================================

    // /**
    //  * Services rattachés à cette structure (composition forte — RG-01).
    //  * Si la structure est supprimée, ses services le sont aussi.
    //  *
    //  * @var Collection<int, Department>
    //  */
    // #[ORM\OneToMany(
    //     mappedBy: 'structure',
    //     targetEntity: Department::class,
    //     cascade: ['persist', 'remove'],
    //     orphanRemoval: true
    // )]
    // private Collection $departments;

    // /**
    //  * Agents employés directement par cette structure (sans service spécifique).
    //  *
    //  * @var Collection<int, User>
    //  */
    // #[ORM\OneToMany(mappedBy: 'structure', targetEntity: User::class)]
    // private Collection $users;

    // /**
    //  * Chef de structure désigné (agent ayant le rôle ROLE_CHIEF_STRUCTURE).
    //  * Référence souple : si l'agent quitte, on peut désigner un nouveau chef.
    //  */
    // #[ORM\ManyToOne(targetEntity: User::class)]
    // #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    // private ?User $chiefStructure = null;

    // /**
    //  * Recommandations à exécuter par cette structure.
    //  *
    //  * @var Collection<int, Recommendation>
    //  */
    // #[ORM\OneToMany(mappedBy: 'executingStructure', targetEntity: Recommendation::class)]
    // private Collection $recommendationsToExecute;

    // /**
    //  * Recommandations suivies par cette structure.
    //  *
    //  * @var Collection<int, Recommendation>
    //  */
    // #[ORM\OneToMany(mappedBy: 'followupStructure', targetEntity: Recommendation::class)]
    // private Collection $recommendationsToFollowup;

    // ============================================================
    // CONSTRUCTEUR
    // ============================================================

    public function __construct()
    {
        $this->active = true;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        // À décommenter quand les relations seront actives :
        // $this->departments = new ArrayCollection();
        // $this->users = new ArrayCollection();
        // $this->recommendationsToExecute = new ArrayCollection();
        // $this->recommendationsToFollowup = new ArrayCollection();
    }

    // ============================================================
    // CALLBACKS DOCTRINE (mise à jour automatique des timestamps)
    // ============================================================

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ============================================================
    // GETTERS / SETTERS
    // ============================================================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        // Normalisation automatique : majuscules + trim
        $this->code = strtoupper(trim($code));
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = trim($label);
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Retourne le libellé humain du type (ex: "Direction" au lieu de "DIRECTION").
     */
    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type ?? '';
    }

    public function getChiefEmail(): ?string
    {
        return $this->chiefEmail;
    }

    public function setChiefEmail(?string $chiefEmail): static
    {
        $this->chiefEmail = $chiefEmail ? trim(strtolower($chiefEmail)) : null;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    /**
     * Active la structure (réactivation après archivage).
     */
    public function activate(): static
    {
        $this->active = true;
        return $this;
    }

    /**
     * Désactive la structure (archivage logique).
     * On ne supprime jamais une structure pour préserver la traçabilité
     * des recommandations historiques.
     */
    public function deactivate(): static
    {
        $this->active = false;
        return $this;
    }

    /**
     * Indique si cette structure est de type Direction.
     */
    public function isDirection(): bool
    {
        return $this->type === self::TYPE_DIRECTION;
    }

    /**
     * Représentation textuelle (utile pour les formulaires et logs).
     */
    public function __toString(): string
    {
        return sprintf('%s — %s', $this->code ?? '???', $this->label ?? '');
    }

    // ============================================================
    // MÉTHODES POUR LES RELATIONS (à décommenter plus tard)
    // ============================================================

    // /**
    //  * @return Collection<int, Department>
    //  */
    // public function getDepartments(): Collection
    // {
    //     return $this->departments;
    // }

    // public function addDepartment(Department $department): static
    // {
    //     if (!$this->departments->contains($department)) {
    //         $this->departments->add($department);
    //         $department->setStructure($this);
    //     }
    //     return $this;
    // }

    // public function removeDepartment(Department $department): static
    // {
    //     if ($this->departments->removeElement($department)) {
    //         if ($department->getStructure() === $this) {
    //             $department->setStructure(null);
    //         }
    //     }
    //     return $this;
    // }

    // public function getChiefStructure(): ?User
    // {
    //     return $this->chiefStructure;
    // }

    // public function setChiefStructure(?User $chiefStructure): static
    // {
    //     $this->chiefStructure = $chiefStructure;
    //     return $this;
    // }

    /**
     * @return Collection<int, Department>
     */
    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function addDepartment(Department $department): static
    {
        if (!$this->departments->contains($department)) {
            $this->departments->add($department);
            $department->setStructure($this);
        }

        return $this;
    }

    public function removeDepartment(Department $department): static
    {
        if ($this->departments->removeElement($department)) {
            // set the owning side to null (unless already changed)
            if ($department->getStructure() === $this) {
                $department->setStructure(null);
            }
        }

        return $this;
    }
}