<?php

declare(strict_types=1);

namespace App\Domain\Venue;

use App\Domain\Common\ValueObject\PaginatedList;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Venue\ValueObject\Address;
use App\Domain\Venue\ValueObject\VenueId;

interface VenueRepositoryInterface
{
    public function listVenues(
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?string $cityFilter = null,
        ?string $countryFilter = null,
    ): PaginatedList;

    public function getById(VenueId $id): Venue;

    public function save(Venue $venue): void;

    public function remove(Venue $venue): void;

    public function findByName(string $name): ?Venue;

    public function findByAddress(Address $address): ?Venue;
}
