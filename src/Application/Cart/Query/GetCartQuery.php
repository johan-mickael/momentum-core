<?php

declare(strict_types=1);

namespace Application\Cart\Query;

final readonly class GetCartQuery
{
    public function __construct(
        public string $eventId,
    ) {
    }
}
