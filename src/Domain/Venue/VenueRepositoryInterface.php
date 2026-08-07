<?php

declare(strict_types=1);

namespace Domain\Venue;

use Domain\Common\ValueObject\PaginatedList;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Venue\ValueObject\Address;
use Domain\Venue\ValueObject\VenueId;

interface VenueRepositoryInterface
{
    public function listVenues(
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?string $cityFilter = null,
        ?string $countryFilter = null,
    ): PaginatedList;

    public function getById(VenueId $id): Venue;

    /**
     * @param VenueId[] $ids
     *
     * @return Venue[]
     */
    public function findByIds(array $ids): array;

    public function save(Venue $venue): void;

    public function remove(Venue $venue): void;

    public function findByName(string $name): ?Venue;

    public function findByAddress(Address $address): ?Venue;
}
