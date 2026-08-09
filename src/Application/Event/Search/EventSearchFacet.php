<?php

declare(strict_types=1);

namespace Application\Event\Search;

final readonly class EventSearchFacet
{
    public function __construct(
        public string $value,
        public int $count,
    ) {
    }
}
