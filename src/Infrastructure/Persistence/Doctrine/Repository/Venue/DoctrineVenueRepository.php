<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Venue;

use App\Domain\Common\ValueObject\PaginatedList;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\Domain\Venue\Exception\VenueNotFound;
use App\Domain\Venue\ValueObject\Address;
use App\Domain\Venue\ValueObject\VenueId;
use App\Domain\Venue\Venue;
use App\Domain\Venue\VenueRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\VenueEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\VenueMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

final readonly class DoctrineVenueRepository implements VenueRepositoryInterface
{
    private const array SORT_FIELD_MAP = [
        'name' => 'v.name',
        'city' => 'v.city',
        'country' => 'v.country',
        'capacity' => 'v.capacity',
    ];
    private const string DEFAULT_SORT_FIELD = 'v.name';

    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function listVenues(
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?string $cityFilter = null,
        ?string $countryFilter = null,
    ): PaginatedList {
        $qb = $this->em->createQueryBuilder()
            ->select('v')
            ->from(VenueEntity::class, 'v');

        if (null !== $cityFilter) {
            $qb->andWhere('LOWER(v.city) LIKE LOWER(:city)')
               ->setParameter('city', '%'.$cityFilter.'%');
        }

        if (null !== $countryFilter) {
            $qb->andWhere('LOWER(v.country) LIKE LOWER(:country)')
               ->setParameter('country', '%'.$countryFilter.'%');
        }

        $sortColumn = self::SORT_FIELD_MAP[$sort->field] ?? self::DEFAULT_SORT_FIELD;
        $qb->orderBy($sortColumn, SortDirection::DESC === $sort->direction ? 'DESC' : 'ASC')
           ->setFirstResult($pagination->offset())
           ->setMaxResults($pagination->limit);

        $paginator = new Paginator($qb->getQuery());

        return new PaginatedList(
            items: array_map(
                static fn (VenueEntity $e): Venue => VenueMapper::toDomain($e),
                iterator_to_array($paginator->getIterator()),
            ),
            total: count($paginator),
            page: $pagination->page,
            limit: $pagination->limit,
        );
    }

    public function getById(VenueId $id): Venue
    {
        $entity = $this->em->find(VenueEntity::class, $id);

        if (null === $entity) {
            throw VenueNotFound::forId($id);
        }

        return VenueMapper::toDomain($entity);
    }

    public function save(Venue $venue): void
    {
        $this->em->persist(VenueMapper::toEntity($venue));
    }

    public function remove(Venue $venue): void
    {
        $entity = VenueMapper::toEntity($venue);
        $this->em->remove($entity);
    }

    public function findByName(string $name): ?Venue
    {
        $entity = $this->em->getRepository(VenueEntity::class)->findOneBy(['name' => $name]);

        return $entity ? VenueMapper::toDomain($entity) : null;
    }

    public function findByAddress(Address $address): ?Venue
    {
        $entity = $this->em->getRepository(VenueEntity::class)->findOneBy([
            'street' => $address->street(),
            'city' => $address->city(),
            'country' => $address->country(),
            'zipCode' => $address->zipCode(),
        ]);

        return $entity ? VenueMapper::toDomain($entity) : null;
    }
}
