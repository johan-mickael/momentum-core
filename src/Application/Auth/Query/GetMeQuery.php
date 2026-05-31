<?php

declare(strict_types=1);

namespace Application\Auth\Query;

final readonly class GetMeQuery
{
    public function __construct(
        public string $userId,
    ) {
    }
}
