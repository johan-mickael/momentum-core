<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\Exception\CannotAddCategoryToEvent;
use App\Domain\Event\Exception\InvalidEventName;
use App\Domain\Event\Exception\InvalidEventStatusTransition;
use App\Domain\Event\Exception\TicketCategoryNotFound;
use App\Domain\Event\ValueObject\DateRange;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\EventSlug;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Venue\ValueObject\VenueId;
use DateTimeImmutable;

final class Event
{
    /** @var TicketCategory[] */
    private array $ticketCategories = [];

    private function __construct(
        private readonly EventId $id,
        private string $name,
        private VenueId $venueId,
        private readonly EventSlug $slug,
        private DateRange $dateRange,
        private EventStatus $status,
        private DateTimeImmutable $createdAt,
        private ?string $description,
        private ?string $imageUrl,
    ) {
        $this->assertValidName($name);
    }

    public static function create(
        EventId $id,
        string $name,
        VenueId $venueId,
        EventSlug $slug,
        DateRange $dateRange,
        DateTimeImmutable $createdAt,
        ?string $description = null,
        ?string $imageUrl = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            venueId: $venueId,
            slug: $slug,
            dateRange: $dateRange,
            status: EventStatus::DRAFT,
            createdAt: $createdAt,
            description: $description,
            imageUrl: $imageUrl,
        );
    }

    public function publish(): void
    {
        if ($this->status !== EventStatus::DRAFT) {
            throw InvalidEventStatusTransition::cannotPublish($this->status);
        }

        $this->status = EventStatus::PUBLISHED;
    }

    public function cancel(): void
    {
        if ($this->status === EventStatus::CANCELLED) {
            throw InvalidEventStatusTransition::cannotCancel($this->status);
        }

        if ($this->status === EventStatus::ENDED) {
            throw InvalidEventStatusTransition::cannotCancel($this->status);
        }

        $this->status = EventStatus::CANCELLED;
    }

    public function end(): void
    {
        if ($this->status !== EventStatus::PUBLISHED) {
            throw InvalidEventStatusTransition::cannotEnd($this->status);
        }

        $this->status = EventStatus::ENDED;
    }

    public function reschedule(DateRange $dateRange): void
    {
        if ($this->status === EventStatus::CANCELLED) {
            throw InvalidEventStatusTransition::cannotReschedule($this->status);
        }

        if ($this->status === EventStatus::ENDED) {
            throw InvalidEventStatusTransition::cannotReschedule($this->status);
        }

        $this->dateRange = $dateRange;
    }

    public function updateDetails(
        string $name,
        ?string $description,
        ?string $imageUrl,
    ): void {
        $this->assertValidName($name);
        $this->name = $name;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
    }

    public function relocate(VenueId $venueId): void
    {
        if ($this->status === EventStatus::CANCELLED) {
            throw InvalidEventStatusTransition::cannotRelocate($this->status);
        }

        $this->venueId = $venueId;
    }

    public function addTicketCategory(
        TicketCategoryId $id,
        string $name,
        Money $price,
        int $totalQuantity,
    ): void {
        if (!$this->isEventActive()) {
            throw CannotAddCategoryToEvent::eventIsNotActive($this->status);
        }

        $this->ticketCategories[] = TicketCategory::create(
            id: $id,
            name: $name,
            price: $price,
            totalQuantity: $totalQuantity,
        );
    }

    private function isEventActive(): bool
    {
        return $this->status === EventStatus::DRAFT || $this->status === EventStatus::PUBLISHED;
    }

    public function reserveTicketInCategory(TicketCategoryId $categoryId): void
    {
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

    public function status(): EventStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
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
        if (trim($name) === '') {
            throw InvalidEventName::create();
        }
    }
}
