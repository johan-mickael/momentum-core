<?php

declare(strict_types=1);

namespace Application\Event\Result;

final readonly class EventSearchResult
{
    public function __construct(
        public string $id,
        public string $name,
        public VenueSummary $venue,
        public string $slug,
        public string $genre,
        public string $startAt,
        public string $endAt,
        public string $status,
        public ?string $description,
        public ?string $imageUrl,
        public ?string $heroImageUrl,
        public ?int $minPrice,
        public ?int $maxPrice,
        public ?string $currency,
        public int $popularity,
        public bool $featured,
        public ?int $featuredRank,
    ) {
    }
}
