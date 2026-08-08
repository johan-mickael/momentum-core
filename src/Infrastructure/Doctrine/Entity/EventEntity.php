<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Domain\Event\EventGenre;
use Domain\Event\EventStatus;

#[ORM\Entity]
#[ORM\Table(name: 'event')]
#[ORM\Index(name: 'idx_event_status', columns: ['status'])]
class EventEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column]
    private string $name;

    #[ORM\ManyToOne(targetEntity: VenueEntity::class)]
    #[ORM\JoinColumn(name: 'venue_id', nullable: false)]
    private VenueEntity $venue;

    #[ORM\Column]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(enumType: EventStatus::class)]
    private EventStatus $status;

    #[ORM\Column(enumType: EventGenre::class)]
    private EventGenre $genre;

    #[ORM\Column]
    private \DateTimeImmutable $startAt;

    #[ORM\Column]
    private \DateTimeImmutable $endAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, TicketCategoryEntity> */
    #[ORM\OneToMany(targetEntity: TicketCategoryEntity::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $ticketCategories;

    public function __construct(
        string $id,
        VenueEntity $venue,
        string $name,
        string $slug,
        \DateTimeImmutable $startAt,
        \DateTimeImmutable $endAt,
        EventStatus $status,
        EventGenre $genre,
        ?string $description = null,
        ?string $imageUrl = null,
    ) {
        $this->id = $id;
        $this->venue = $venue;
        $this->name = $name;
        $this->slug = $slug;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->status = $status;
        $this->genre = $genre;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->createdAt = new \DateTimeImmutable();
        $this->ticketCategories = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getVenue(): VenueEntity
    {
        return $this->venue;
    }

    public function setVenue(VenueEntity $venue): static
    {
        $this->venue = $venue;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    public function setStatus(EventStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getGenre(): EventGenre
    {
        return $this->genre;
    }

    public function setGenre(EventGenre $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return Collection<int, TicketCategoryEntity> */
    public function getTicketCategories(): Collection
    {
        return $this->ticketCategories;
    }

    public function addTicketCategory(TicketCategoryEntity $category): void
    {
        $this->ticketCategories->add($category);
    }
}
