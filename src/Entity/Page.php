<?php

namespace App\Entity;

use App\Repository\PageRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Une page avec ce slug existe déjà.')]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, unique: true)]
    private ?string $slug = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Page $parent = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titleFr = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titleEn = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $subtitleFr = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $subtitleEn = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 70)]
    #[ORM\Column(length: 255)]
    private ?string $metaTitleFr = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 70)]
    #[ORM\Column(length: 255)]
    private ?string $metaTitleEn = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    #[ORM\Column(length: 255)]
    private ?string $metaDescriptionFr = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    #[ORM\Column(length: 255)]
    private ?string $metaDescriptionEn = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    /** @var Collection<int, Page> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getParent(): ?Page
    {
        return $this->parent;
    }

    public function setParent(?Page $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTitleFr(): ?string
    {
        return $this->titleFr;
    }

    public function setTitleFr(?string $titleFr): static
    {
        $this->titleFr = $titleFr;

        return $this;
    }

    public function getTitleEn(): ?string
    {
        return $this->titleEn;
    }

    public function setTitleEn(?string $titleEn): static
    {
        $this->titleEn = $titleEn;

        return $this;
    }

    public function getSubtitleFr(): ?string
    {
        return $this->subtitleFr;
    }

    public function setSubtitleFr(?string $subtitleFr): static
    {
        $this->subtitleFr = $subtitleFr;

        return $this;
    }

    public function getSubtitleEn(): ?string
    {
        return $this->subtitleEn;
    }

    public function setSubtitleEn(?string $subtitleEn): static
    {
        $this->subtitleEn = $subtitleEn;

        return $this;
    }

    public function getMetaTitleFr(): ?string
    {
        return $this->metaTitleFr;
    }

    public function setMetaTitleFr(string $metaTitleFr): static
    {
        $this->metaTitleFr = $metaTitleFr;

        return $this;
    }

    public function getMetaTitleEn(): ?string
    {
        return $this->metaTitleEn;
    }

    public function setMetaTitleEn(string $metaTitleEn): static
    {
        $this->metaTitleEn = $metaTitleEn;

        return $this;
    }

    public function getMetaDescriptionFr(): ?string
    {
        return $this->metaDescriptionFr;
    }

    public function setMetaDescriptionFr(string $metaDescriptionFr): static
    {
        $this->metaDescriptionFr = $metaDescriptionFr;

        return $this;
    }

    public function getMetaDescriptionEn(): ?string
    {
        return $this->metaDescriptionEn;
    }

    public function setMetaDescriptionEn(string $metaDescriptionEn): static
    {
        $this->metaDescriptionEn = $metaDescriptionEn;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    // Helper
    public function isEffectivelyEnabled(): bool
    {
        return $this->enabled
            && ($this->parent === null || $this->parent->isEffectivelyEnabled());
    }

    /** @return Collection<int, Page> */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}
