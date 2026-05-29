<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Event;

use App\Domain\Common\ValueObject\PaginatedList;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\Domain\Event\Event;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\EventStatus;
use App\Domain\Event\Exception\EventNotFound;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\EventVisibility;
use App\Infrastructure\Persistence\Doctrine\Entity\EventEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\EventMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

final readonly class DoctrineEventRepository implements EventRepositoryInterface
{
    private const array SORT_FIELD_MAP = [
        'startAt'   => 'e.startAt',
        'createdAt' => 'e.createdAt',
        'name'      => 'e.name',
    ];
    private const string DEFAULT_SORT_FIELD = 'e.startAt';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventMapper            $eventMapper,
    ) {}

    public function create(Event $event): void
    {
        $this->entityManager->persist($this->eventMapper->toEntity($event));
    }

    public function save(Event $event): void
    {
        $entity = $this->entityManager->find(EventEntity::class, (string) $event->id());

        if ($entity === null) {
            $this->entityManager->persist($this->eventMapper->toEntity($event));
            return;
        }

        $this->eventMapper->updateEntity($entity, $event);
    }

    public function getById(EventId $id): Event
    {
        $entity = $this->entityManager->getRepository(EventEntity::class)->find($id);

        if (is_null($entity)) {
            throw new EventNotFound();
        }

        return $this->eventMapper->toDomain($entity);
    }

    public function remove(Event $event): void
    {
        $entity = $this->entityManager->find(EventEntity::class, (string) $event->id());

        if (is_null($entity)) {
            throw new EventNotFound();
        }

        $this->entityManager->remove($entity);
    }

    public function list(
        EventVisibility    $visibility,
        PaginationCriteria $pagination,
        SortCriteria       $sort,
        ?EventStatus       $statusFilter = null,
    ): PaginatedList {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(EventEntity::class, 'e');

        if ($visibility === EventVisibility::PUBLISHED) {
            $qb->andWhere('e.status = :publishedStatus')
               ->setParameter('publishedStatus', EventStatus::PUBLISHED);
        } elseif ($statusFilter !== null) {
            $qb->andWhere('e.status = :statusFilter')
               ->setParameter('statusFilter', $statusFilter);
        }

        $sortColumn = self::SORT_FIELD_MAP[$sort->field] ?? self::DEFAULT_SORT_FIELD;
        $qb->orderBy($sortColumn, $sort->direction === SortDirection::DESC ? 'DESC' : 'ASC')
           ->setFirstResult($pagination->offset())
           ->setMaxResults($pagination->limit);

        $paginator = new Paginator($qb->getQuery());

        return new PaginatedList(
            items: array_map(
                fn(EventEntity $e) => $this->eventMapper->toDomain($e),
                iterator_to_array($paginator->getIterator()),
            ),
            total: count($paginator),
            page:  $pagination->page,
            limit: $pagination->limit,
        );
    }
}
