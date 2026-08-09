<?php

declare(strict_types=1);

namespace Application\Event\Command;

class CreateEventCommand
{
    private function __construct(
        public string $name,
        public string $venueId,
        public string $eventSlug,
        public \DateTimeImmutable $startAt,
        public \DateTimeImmutable $endAt,
        public ?string $description,
        public ?string $imageUrl,
        public ?string $heroImageUrl,
        public string $genre,
    ) {
    }

    public static function create(
        string $name,
        string $venueId,
        string $eventSlug,
        string $startAt,
        string $endAt,
        ?string $description,
        ?string $imageUrl,
        ?string $heroImageUrl,
        string $genre,
    ): self {
        $startAt = new \DateTimeImmutable($startAt);
        $endAt = new \DateTimeImmutable($endAt);

        return new self(
            $name,
            $venueId,
            $eventSlug,
            $startAt,
            $endAt,
            $description,
            $imageUrl,
            $heroImageUrl,
            $genre,
        );
    }
}
