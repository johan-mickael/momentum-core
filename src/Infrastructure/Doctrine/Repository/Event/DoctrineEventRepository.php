<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Repository\Event;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Domain\Common\ValueObject\PaginatedList;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Common\ValueObject\SortDirection;
use Domain\Event\Event;
use Domain\Event\EventRepositoryInterface;
use Domain\Event\EventStatus;
use Domain\Event\Exception\EventNotFound;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\EventVisibility;
use Infrastructure\Doctrine\Entity\EventEntity;
use Infrastructure\Doctrine\Mapper\EventMapper;

final readonly class DoctrineEventRepository implements EventRepositoryInterface
{
    private const array SORT_FIELD_MAP = [
        'startAt' => 'e.startAt',
        'createdAt' => 'e.createdAt',
        'name' => 'e.name',
    ];
    private const string DEFAULT_SORT_FIELD = 'e.startAt';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventMapper $eventMapper,
    ) {
    }

    public function create(Event $event): void
    {
        $this->entityManager->persist($this->eventMapper->toEntity($event));
    }

    public function save(Event $event): void
    {
        $entity = $this->entityManager->find(EventEntity::class, (string) $event->id());

        if (null === $entity) {
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

    public function getByIdForUpdate(EventId $id): Event
    {
        $entity = $this->entityManager->find(EventEntity::class, (string) $id, LockMode::PESSIMISTIC_WRITE);

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
        EventVisibility $visibility,
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?EventStatus $statusFilter = null,
    ): PaginatedList {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(EventEntity::class, 'e');

        if (EventVisibility::PUBLISHED === $visibility) {
            $qb->andWhere('e.status = :publishedStatus')
               ->setParameter('publishedStatus', EventStatus::PUBLISHED);
        } elseif (null !== $statusFilter) {
            $qb->andWhere('e.status = :statusFilter')
               ->setParameter('statusFilter', $statusFilter);
        }

        $sortColumn = self::SORT_FIELD_MAP[$sort->field] ?? self::DEFAULT_SORT_FIELD;
        $qb->orderBy($sortColumn, SortDirection::DESC === $sort->direction ? 'DESC' : 'ASC')
           ->setFirstResult($pagination->offset())
           ->setMaxResults($pagination->limit);

        $paginator = new Paginator($qb->getQuery());

        return new PaginatedList(
            items: array_map(
                fn (EventEntity $e) => $this->eventMapper->toDomain($e),
                iterator_to_array($paginator->getIterator()),
            ),
            total: count($paginator),
            page: $pagination->page,
            limit: $pagination->limit,
        );
    }
}
