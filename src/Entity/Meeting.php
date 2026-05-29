<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MeetingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MeetingRepository::class)]
#[ORM\Table(name: 'meetings')]
#[ORM\HasLifecycleCallbacks]
class Meeting implements \Stringable
{
    // Types de séance (cohérents avec ta maquette Sessions)
    public const TYPE_AUDIT_INTERNE = 'audit_interne';
    public const TYPE_REUNION_DIRECTION = 'reunion_direction';
    public const TYPE_COMITE_TECHNIQUE = 'comite_technique';

    public const TYPES = [
        self::TYPE_AUDIT_INTERNE => 'Audit interne',
        self::TYPE_REUNION_DIRECTION => 'Réunion de direction',
        self::TYPE_COMITE_TECHNIQUE => 'Comité technique',
    ];

    // Statuts d'une séance
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_DONE = 'done';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Brouillon',
        self::STATUS_SCHEDULED => 'Programmée',
        self::STATUS_DONE => 'Terminée',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre de la séance est obligatoire.')]
    private ?string $title = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le type de séance est obligatoire.')]
    private ?string $type = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_SCHEDULED;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->status = self::STATUS_SCHEDULED;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Avant le premier enregistrement, on génère une référence unique
     * du type SEA-2026-0001 si elle n'a pas été définie.
     */
    #[ORM\PrePersist]
    public function generateReference(): void
    {
        if ($this->reference === null) {
            $this->reference = 'SEA-' . date('Y') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim($title);
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

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Inconnu';
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

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return ($this->reference ?? '???') . ' — ' . ($this->title ?? '');
    }
}
