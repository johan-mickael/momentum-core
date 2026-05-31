<?php

declare(strict_types=1);

namespace Domain\Venue\Security\Authorization;

use Domain\Common\Security\Authorization\Subject;
use Domain\Venue\Venue;

final readonly class VenueContext implements Subject
{
    public function __construct(
        public Venue $venue,
    ) {
    }
}
