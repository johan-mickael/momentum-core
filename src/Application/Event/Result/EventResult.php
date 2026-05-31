<?php

declare(strict_types=1);

namespace Application\Event\Result;

final readonly class EventResult
{
    public function __construct(
        public string $id,
        public string $name,
        public VenueSummary $venue,
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
