<?php

declare(strict_types=1);

namespace Domain\Venue\Service;

use Domain\Venue\Exception\DuplicateVenueName;
use Domain\Venue\Venue;
use Domain\Venue\VenueRepositoryInterface;

final class VenueNameUniquenessChecker
{
    private VenueRepositoryInterface $venueRepository;

    public function __construct(VenueRepositoryInterface $venueRepository)
    {
        $this->venueRepository = $venueRepository;
    }

    public function check(Venue $venue): void
    {
        if (empty($this->venueRepository->findByName($venue->name()))) {
            return;
        }

        throw DuplicateVenueName::for($venue->name());
    }
}
