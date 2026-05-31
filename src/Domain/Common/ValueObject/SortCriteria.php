<?php

declare(strict_types=1);

namespace Domain\Common\ValueObject;

final readonly class SortCriteria
{
    public function __construct(
        public readonly string $field,
        public readonly SortDirection $direction,
    ) {
    }
}
