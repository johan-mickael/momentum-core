<?php

declare(strict_types=1);

namespace Domain\Event;

use Domain\Common\Event\RecordsDomainEvents;
use Domain\Common\Exception\CurrencyMismatch;
use Domain\Common\ValueObject\Money;
use Domain\Event\DomainEvent\EventCancelled;
use Domain\Event\DomainEvent\EventCreated;
use Domain\Event\DomainEvent\EventPublished;
use Domain\Event\Exception\CannotAddCategoryToEvent;
use Domain\Event\Exception\CannotRemoveTicketCategoryWithReservations;
use Domain\Event\Exception\DuplicateTicketCategoryNameForEvent;
use Domain\Event\Exception\EventHasNoTicketCategories;
use Domain\Event\Exception\InvalidEventName;
use Domain\Event\Exception\InvalidEventStatusTransition;
use Domain\Event\Exception\TicketCategoryNotFound;
use Domain\Event\Exception\TicketsCannotBeReserved;
use Domain\Event\ValueObject\DateRange;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\EventSlug;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\Venue\ValueObject\VenueId;

final class Event
{
    use RecordsDomainEvents;

    /** @var TicketCategory[] */
    private array $ticketCategories = [];

    private function __construct(
        private readonly EventId $id,
        private string $name,
        private VenueId $venueId,
        private readonly EventSlug $slug,
        private DateRange $dateRange,
        private EventStatus $status,
        private \DateTimeImmutable $createdAt,
        private ?string $description,
        private ?string $imageUrl,
        private ?string $heroImageUrl,
        private EventGenre $genre,
    ) {
        $this->assertValidName($name);
    }

    public static function create(
        EventId $id,
        string $name,
        VenueId $venueId,
        EventSlug $slug,
        DateRange $dateRange,
        \DateTimeImmutable $createdAt,
        ?string $description = null,
        ?string $imageUrl = null,
        ?string $heroImageUrl = null,
        EventGenre $genre = EventGenre::OTHER,
    ): self {
        $event = new self(
            id: $id,
            name: $name,
            venueId: $venueId,
            slug: $slug,
            dateRange: $dateRange,
            status: EventStatus::DRAFT,
            createdAt: $createdAt,
            description: $description,
            imageUrl: $imageUrl,
            heroImageUrl: $heroImageUrl,
            genre: $genre,
        );

        $event->recordEvent(new EventCreated(eventId: (string) $id));

        return $event;
    }

    /**
     * @param TicketCategory[] $ticketCategories
     */
    public static function reconstitute(
        EventId $id,
        string $name,
        VenueId $venueId,
        EventSlug $slug,
        DateRange $dateRange,
        EventStatus $status,
        \DateTimeImmutable $createdAt,
        array $ticketCategories = [],
        ?string $description = null,
        ?string $imageUrl = null,
        ?string $heroImageUrl = null,
        EventGenre $genre = EventGenre::OTHER,
    ): self {
        $event = new self(
            id: $id,
            name: $name,
            venueId: $venueId,
            slug: $slug,
            dateRange: $dateRange,
            status: $status,
            createdAt: $createdAt,
            description: $description,
            imageUrl: $imageUrl,
            heroImageUrl: $heroImageUrl,
            genre: $genre,
        );

        $event->ticketCategories = $ticketCategories;

        return $event;
    }

    public function publish(): void
    {
        if (EventStatus::DRAFT !== $this->status) {
            throw InvalidEventStatusTransition::cannotPublish($this->status);
        }

        if (empty($this->ticketCategories)) {
            throw EventHasNoTicketCategories::create();
        }

        $this->status = EventStatus::PUBLISHED;

        $this->recordEvent(new EventPublished(eventId: (string) $this->id));
    }

    public function cancel(): void
    {
        if (EventStatus::CANCELLED === $this->status) {
            throw InvalidEventStatusTransition::cannotCancel($this->status);
        }

        if (EventStatus::ENDED === $this->status) {
            throw InvalidEventStatusTransition::cannotCancel($this->status);
        }

        $this->status = EventStatus::CANCELLED;

        $this->recordEvent(new EventCancelled(eventId: (string) $this->id));
    }

    public function end(): void
    {
        if (EventStatus::PUBLISHED !== $this->status) {
            throw InvalidEventStatusTransition::cannotEnd($this->status);
        }

        $this->status = EventStatus::ENDED;
    }

    public function reschedule(DateRange $dateRange): void
    {
        if (EventStatus::CANCELLED === $this->status) {
            throw InvalidEventStatusTransition::cannotReschedule($this->status);
        }

        if (EventStatus::ENDED === $this->status) {
            throw InvalidEventStatusTransition::cannotReschedule($this->status);
        }

        $this->dateRange = $dateRange;
    }

    public function updateDetails(
        string $name,
        ?string $description,
        ?string $imageUrl,
        ?EventGenre $genre = null,
        ?string $heroImageUrl = null,
    ): void {
        $this->assertValidName($name);
        $this->name = $name;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->heroImageUrl = $heroImageUrl;
        $this->genre = $genre ?? $this->genre;
    }

    public function relocate(VenueId $venueId): void
    {
        if (EventStatus::CANCELLED === $this->status) {
            throw InvalidEventStatusTransition::cannotRelocate($this->status);
        }

        $this->venueId = $venueId;
    }

    public function addTicketCategory(
        TicketCategoryId $id,
        string $name,
        Money $price,
        int $totalQuantity,
    ): TicketCategory {
        if (!$this->isEventActive()) {
            throw CannotAddCategoryToEvent::eventIsNotActive($this->status);
        }

        $this->assertCurrencyMatchesExistingCategories($price);

        $category = TicketCategory::create(
            id: $id,
            name: $name,
            price: $price,
            totalQuantity: $totalQuantity,
        );

        $this->assertUniqueTicketCategoryName($category);

        $this->ticketCategories[] = $category;

        return $category;
    }

    private function assertCurrencyMatchesExistingCategories(Money $price, ?TicketCategoryId $excludedId = null): void
    {
        foreach ($this->ticketCategories as $category) {
            if (null !== $excludedId && $category->id()->equals($excludedId)) {
                continue;
            }

            if (!$category->price()->currency()->equals($price->currency())) {
                throw CurrencyMismatch::between($category->price()->currency(), $price->currency());
            }
        }
    }

    private function assertUniqueTicketCategoryName(TicketCategory $category): void
    {
        if (array_any($this->ticketCategories(), fn ($ticketCategory) => $ticketCategory->name() === $category->name())) {
            throw DuplicateTicketCategoryNameForEvent::withName($category->name());
        }
    }

    private function assertUniqueTicketCategoryNameExcluding(string $name, TicketCategoryId $excludedId): void
    {
        foreach ($this->ticketCategories as $category) {
            if (!$category->id()->equals($excludedId) && $category->name() === $name) {
                throw DuplicateTicketCategoryNameForEvent::withName($name);
            }
        }
    }

    private function isEventActive(): bool
    {
        return EventStatus::DRAFT === $this->status || EventStatus::PUBLISHED === $this->status;
    }

    public function updateTicketCategory(
        TicketCategoryId $id,
        string $name,
        Money $price,
        int $totalQuantity,
    ): TicketCategory {
        $category = $this->findCategory($id);
        $this->assertUniqueTicketCategoryNameExcluding($name, $id);
        $this->assertCurrencyMatchesExistingCategories($price, $id);
        $category->update($name, $price, $totalQuantity);

        return $category;
    }

    public function removeTicketCategory(TicketCategoryId $id): void
    {
        $category = $this->findCategory($id);

        if ($category->reservedQuantity() > 0) {
            throw CannotRemoveTicketCategoryWithReservations::forCategory($id);
        }

        $this->ticketCategories = array_values(
            array_filter($this->ticketCategories, fn (TicketCategory $c) => !$c->id()->equals($id)),
        );
    }

    public function reserveTicketInCategory(TicketCategoryId $categoryId): void
    {
        if (EventStatus::PUBLISHED !== $this->status) {
            throw TicketsCannotBeReserved::eventNotPublished($this->id, $this->status);
        }
        $this->findCategory($categoryId)->reserveOneTicket();
    }

    public function releaseTicketInCategory(TicketCategoryId $categoryId): void
    {
        $this->findCategory($categoryId)->releaseOneTicket();
    }

    /** @return TicketCategory[] */
    public function ticketCategories(): array
    {
        return $this->ticketCategories;
    }

    public function id(): EventId
    {
        return $this->id;
    }

    public function venueId(): VenueId
    {
        return $this->venueId;
    }

    public function slug(): EventSlug
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function dateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function imageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function heroImageUrl(): ?string
    {
        return $this->heroImageUrl;
    }

    public function status(): EventStatus
    {
        return $this->status;
    }

    public function genre(): EventGenre
    {
        return $this->genre;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function categoryById(TicketCategoryId $categoryId): TicketCategory
    {
        return $this->findCategory($categoryId);
    }

    private function findCategory(TicketCategoryId $categoryId): TicketCategory
    {
        foreach ($this->ticketCategories as $category) {
            if ($category->id()->equals($categoryId)) {
                return $category;
            }
        }

        throw TicketCategoryNotFound::withId($categoryId);
    }

    private function assertValidName(string $name): void
    {
        if ('' === trim($name)) {
            throw InvalidEventName::create();
        }
    }
}
