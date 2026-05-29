<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Event — la trace d'un changement dans le cycle de vie d'une recommandation.
 *
 * Règle métier RG-07 : chaque transition de statut est historisée.
 * On enregistre : quelle reco, de quel statut vers quel statut,
 * qui l'a fait, quand, et un éventuel commentaire (ex: motif de renvoi).
 */
#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
#[ORM\HasLifecycleCallbacks]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // La recommandation concernée. Si on la supprime, ses événements
    // disparaissent aussi (cascade) : un historique sans sa reco n'a pas de sens.
    #[ORM\ManyToOne(targetEntity: Recommendation::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Recommendation $recommendation = null;

    // Statut de départ (peut être null pour le tout premier événement = création)
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $fromStatus = null;

    // Statut d'arrivée
    #[ORM\Column(length: 10)]
    private ?string $toStatus = null;

    // L'agent qui a déclenché la transition (nullable : certaines transitions
    // sont automatiques, ex: relance système).
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $author = null;

    // Commentaire optionnel (motif de renvoi, précision...)
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecommendation(): ?Recommendation
    {
        return $this->recommendation;
    }

    public function setRecommendation(?Recommendation $recommendation): static
    {
        $this->recommendation = $recommendation;
        return $this;
    }

    public function getFromStatus(): ?string
    {
        return $this->fromStatus;
    }

    public function setFromStatus(?string $fromStatus): static
    {
        $this->fromStatus = $fromStatus;
        return $this;
    }

    public function getToStatus(): ?string
    {
        return $this->toStatus;
    }

    public function setToStatus(string $toStatus): static
    {
        $this->toStatus = $toStatus;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Libellés lisibles des statuts (réutilise ceux de Recommendation)
    public function getFromStatusLabel(): ?string
    {
        return $this->fromStatus ? (Recommendation::STATUSES[$this->fromStatus] ?? $this->fromStatus) : null;
    }

    public function getToStatusLabel(): string
    {
        return Recommendation::STATUSES[$this->toStatus] ?? $this->toStatus;
    }
}
