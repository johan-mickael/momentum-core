<?php

declare(strict_types=1);

namespace App\Application\Venue\Query;

final readonly class GetVenueByIdQuery
{
    public function __construct(
        public string $id,
    ) {
    }
}
