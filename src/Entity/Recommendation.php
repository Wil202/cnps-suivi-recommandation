<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RecommendationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecommendationRepository::class)]
#[ORM\Table(name: 'recommendations')]
#[ORM\HasLifecycleCallbacks]
class Recommendation implements \Stringable
{
    // ============================================
    // Les 11 statuts du workflow (S0 → S10)
    // ============================================
    public const STATUS_DRAFT = 'S0';
    public const STATUS_VALIDATED = 'S1';
    public const STATUS_ASSIGNED = 'S2';
    public const STATUS_IN_PROGRESS = 'S3';
    public const STATUS_SUBMITTED = 'S4';
    public const STATUS_VALIDATED_CS = 'S5';
    public const STATUS_RETURNED = 'S6';
    public const STATUS_APPROVED = 'S7';
    public const STATUS_RELAUNCHED = 'S8';
    public const STATUS_CLOSED = 'S9';
    public const STATUS_REJECTED = 'S10';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Projet',
        self::STATUS_VALIDATED => 'Validée',
        self::STATUS_ASSIGNED => 'Affectée',
        self::STATUS_IN_PROGRESS => 'En cours',
        self::STATUS_SUBMITTED => 'Soumise CS',
        self::STATUS_VALIDATED_CS => 'Validée CS',
        self::STATUS_RETURNED => 'Renvoyée',
        self::STATUS_APPROVED => 'Approuvée',
        self::STATUS_RELAUNCHED => 'Relancée',
        self::STATUS_CLOSED => 'Clôturée',
        self::STATUS_REJECTED => 'Rejetée',
    ];

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Faible',
        self::PRIORITY_MEDIUM => 'Moyenne',
        self::PRIORITY_HIGH => 'Élevée',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le libellé de la recommandation est obligatoire.')]
    private ?string $label = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 10)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 20)]
    private string $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    // ============================================
    // Relations
    // ============================================

    // La séance d'origine
    #[ORM\ManyToOne(targetEntity: Meeting::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Meeting $meeting = null;

    // La structure à qui la reco est affectée
    #[ORM\ManyToOne(targetEntity: Structure::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Structure $assignedStructure = null;

    // L'agent désigné pour traiter la recommandation (RG-12)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedAgent = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->status = self::STATUS_DRAFT;
        $this->priority = self::PRIORITY_MEDIUM;
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function generateReference(): void
    {
        if ($this->reference === null) {
            $this->reference = 'REC-' . date('Y') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? 'Inconnu';
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? 'Inconnu';
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getMeeting(): ?Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(?Meeting $meeting): static
    {
        $this->meeting = $meeting;
        return $this;
    }

    public function getAssignedStructure(): ?Structure
    {
        return $this->assignedStructure;
    }

    public function setAssignedStructure(?Structure $assignedStructure): static
    {
        $this->assignedStructure = $assignedStructure;
        return $this;
    }

    public function getAssignedAgent(): ?User
    {
        return $this->assignedAgent;
    }

    public function setAssignedAgent(?User $assignedAgent): static
    {
        $this->assignedAgent = $assignedAgent;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * RG-06 : visibilité externe seulement à partir de S7.
     */
    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_RELAUNCHED,
            self::STATUS_CLOSED,
        ], true);
    }

    public function __toString(): string
    {
        return ($this->reference ?? '???') . ' — ' . ($this->label ?? '');
    }
}