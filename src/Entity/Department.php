<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
#[ORM\Table(name: 'departments')]
#[ORM\Index(columns: ['code'], name: 'idx_department_code')]
#[ORM\Index(columns: ['active'], name: 'idx_department_active')]
#[ORM\UniqueConstraint(name: 'uniq_dept_code_per_structure', columns: ['structure_id', 'code'])]
#[ORM\HasLifecycleCallbacks]
class Department implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le code du service est obligatoire.')]
    #[Assert\Length(min: 2, max: 20)]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9_-]+$/',
        message: 'Le code ne peut contenir que des lettres majuscules, chiffres, tirets et underscores.'
    )]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le libellé du service est obligatoire.')]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $label = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Structure::class, inversedBy: 'departments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le service doit être rattaché à une structure.')]
    private ?Structure $structure = null;

    public function __construct()
    {
        $this->active = true;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

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

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function activate(): static
    {
        $this->active = true;
        return $this;
    }

    public function deactivate(): static
    {
        $this->active = false;
        return $this;
    }

    public function getFullCode(): string
    {
        if (!$this->structure || !$this->code) {
            return $this->code ?? '???';
        }
        return $this->structure->getCode() . '/' . $this->code;
    }

    public function __toString(): string
    {
        $structureCode = $this->structure?->getCode() ?? '???';
        return sprintf('%s/%s — %s', $structureCode, $this->code ?? '???', $this->label ?? '');
    }
}
