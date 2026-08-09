<?php

declare(strict_types=1);

namespace Application\Event\Command;

final readonly class UpdateEventCommand
{
    private function __construct(
        public string $eventId,
        public string $name,
        public \DateTimeImmutable $startAt,
        public \DateTimeImmutable $endAt,
        public string $venueId,
        public ?string $description,
        public ?string $imageUrl,
        public ?string $heroImageUrl,
        public string $genre,
    ) {
    }

    public static function create(
        string $eventId,
        string $name,
        string $startAt,
        string $endAt,
        string $venueId,
        ?string $description,
        ?string $imageUrl,
        ?string $heroImageUrl,
        string $genre,
    ): self {
        $startAt = new \DateTimeImmutable($startAt);
        $endAt = new \DateTimeImmutable($endAt);

        return new self(
            $eventId,
            $name,
            $startAt,
            $endAt,
            $venueId,
            $description,
            $imageUrl,
            $heroImageUrl,
            $genre,
        );
    }
}
