<?php

declare(strict_types=1);

namespace App\Application\Event\Result;

final readonly class CreateEventResult
{
    public function __construct(
        public string $id,
        public string $name,
        public string $venueId,
        public string $slug,
        public string $startAt,
        public string $endAt,
        public string $status,
        public string $createdAt,
        public ?string $description,
        public ?string $imageUrl,
    ) {
    }
}
