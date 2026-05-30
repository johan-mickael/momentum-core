<?php

declare(strict_types=1);

namespace App\Domain\Common\ValueObject;

final readonly class SortCriteria
{
    public function __construct(
        public readonly string $field,
        public readonly SortDirection $direction,
    ) {
    }
}
