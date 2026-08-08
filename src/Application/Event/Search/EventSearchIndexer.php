<?php

declare(strict_types=1);

namespace Application\Event\Search;

use Domain\Event\Event;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\TicketCategory;
use Domain\Event\ValueObject\EventId;
use Domain\Venue\Venue;
use Domain\Venue\VenueRepositoryInterface;

final readonly class EventSearchIndexer
{
    public function __construct(
        private EventRepositoryInterface $events,
        private VenueRepositoryInterface $venues,
        private EventSearchIndexInterface $searchIndex,
    ) {
    }

    public function reindex(EventId $id): void
    {
        $event = $this->events->getById($id);
        $venue = $this->venues->getById($event->venueId());

        $this->searchIndex->index($this->toDocument($event, $venue));
    }

    private function toDocument(Event $event, Venue $venue): EventSearchDocument
    {
        [$minPrice, $maxPrice, $currency] = $this->priceRange($event->ticketCategories());

        return new EventSearchDocument(
            id: (string) $event->id(),
            name: $event->name(),
            description: $event->description(),
            genre: $event->genre()->value,
            status: $event->status()->value,
            startAt: $event->dateRange()->startAt->format(\DateTimeInterface::ATOM),
            endAt: $event->dateRange()->endAt->format(\DateTimeInterface::ATOM),
            slug: (string) $event->slug(),
            imageUrl: $event->imageUrl(),
            heroImageUrl: $event->heroImageUrl(),
            venueId: (string) $venue->id(),
            venueName: $venue->name(),
            venueStreet: $venue->address()->street(),
            venueCity: $venue->address()->city(),
            venueZipCode: $venue->address()->zipCode(),
            venueCountry: $venue->address()->country(),
            venueLatitude: $venue->location()->latitude(),
            venueLongitude: $venue->location()->longitude(),
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            currency: $currency,
        );
    }

    /**
     * @param TicketCategory[] $ticketCategories
     *
     * @return array{0: ?int, 1: ?int, 2: ?string}
     */
    private function priceRange(array $ticketCategories): array
    {
        if ([] === $ticketCategories) {
            return [null, null, null];
        }

        $amounts = array_map(fn (TicketCategory $c) => $c->price()->amount(), $ticketCategories);

        return [min($amounts), max($amounts), $ticketCategories[0]->price()->currency()->toString()];
    }
}
