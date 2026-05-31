<?php

declare(strict_types=1);

namespace Domain\Venue\Service;

use Domain\Venue\Exception\DuplicateVenueAddress;
use Domain\Venue\Venue;
use Domain\Venue\VenueRepositoryInterface;

final class VenueAddressUniquenessChecker
{
    private VenueRepositoryInterface $venueRepository;

    public function __construct(VenueRepositoryInterface $venueRepository)
    {
        $this->venueRepository = $venueRepository;
    }

    public function check(Venue $venue): void
    {
        if (empty($this->venueRepository->findByAddress($venue->address()))) {
            return;
        }

        throw DuplicateVenueAddress::for($venue->address());
    }
}
