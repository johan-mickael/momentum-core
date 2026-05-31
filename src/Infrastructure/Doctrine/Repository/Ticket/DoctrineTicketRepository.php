<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Repository\Ticket;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Domain\Common\ValueObject\PaginatedList;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Common\ValueObject\SortDirection;
use Domain\Event\ValueObject\EventId;
use Domain\Ticket\Exception\TicketNotFound;
use Domain\Ticket\Exception\TicketNotFoundByEntryCode;
use Domain\Ticket\Ticket;
use Domain\Ticket\TicketRepositoryInterface;
use Domain\Ticket\TicketStatus;
use Domain\Ticket\ValueObject\EntryCode;
use Domain\Ticket\ValueObject\TicketId;
use Domain\User\ValueObject\UserId;
use Infrastructure\Doctrine\Entity\TicketEntity;
use Infrastructure\Doctrine\Mapper\TicketMapper;

final readonly class DoctrineTicketRepository implements TicketRepositoryInterface
{
    private const array SORT_FIELD_MAP = [
        'reservedAt' => 't.reservedAt',
        'status' => 't.status',
    ];
    private const string DEFAULT_SORT_FIELD = 't.reservedAt';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketMapper $ticketMapper,
    ) {
    }

    public function findById(TicketId $id): Ticket
    {
        $entity = $this->entityManager->find(TicketEntity::class, $id->toString());

        if (null === $entity) {
            throw TicketNotFound::withId($id);
        }

        return $this->ticketMapper->toDomain($entity);
    }

    public function findByEntryCode(EntryCode $entryCode): Ticket
    {
        $entity = $this->entityManager
            ->getRepository(TicketEntity::class)
            ->findOneBy(['entryCode' => $entryCode->toString()]);

        if (null === $entity) {
            throw TicketNotFoundByEntryCode::withCode($entryCode);
        }

        return $this->ticketMapper->toDomain($entity);
    }

    /** @return Ticket[] */
    public function findAllByEvent(EventId $eventId): array
    {
        $entities = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(TicketEntity::class, 't')
            ->where('t.eventId = :eventId')
            ->setParameter('eventId', (string) $eventId)
            ->getQuery()
            ->getResult();

        return array_map(fn (TicketEntity $e) => $this->ticketMapper->toDomain($e), $entities);
    }

    public function findByEvent(
        EventId $eventId,
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?TicketStatus $statusFilter = null,
    ): PaginatedList {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(TicketEntity::class, 't')
            ->where('t.eventId = :eventId')
            ->setParameter('eventId', (string) $eventId);

        if (null !== $statusFilter) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $statusFilter);
        }

        $sortColumn = self::SORT_FIELD_MAP[$sort->field] ?? self::DEFAULT_SORT_FIELD;
        $qb->orderBy($sortColumn, SortDirection::DESC === $sort->direction ? 'DESC' : 'ASC')
           ->setFirstResult($pagination->offset())
           ->setMaxResults($pagination->limit);

        $paginator = new Paginator($qb->getQuery());

        return new PaginatedList(
            items: array_map(fn (TicketEntity $e) => $this->ticketMapper->toDomain($e), iterator_to_array($paginator->getIterator())),
            total: count($paginator),
            page: $pagination->page,
            limit: $pagination->limit,
        );
    }

    public function findByHolder(
        UserId $holderId,
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?TicketStatus $statusFilter = null,
    ): PaginatedList {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(TicketEntity::class, 't')
            ->where('t.holderId = :holderId')
            ->setParameter('holderId', (string) $holderId);

        if (null !== $statusFilter) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $statusFilter);
        }

        $sortColumn = self::SORT_FIELD_MAP[$sort->field] ?? self::DEFAULT_SORT_FIELD;
        $qb->orderBy($sortColumn, SortDirection::DESC === $sort->direction ? 'DESC' : 'ASC')
           ->setFirstResult($pagination->offset())
           ->setMaxResults($pagination->limit);

        $paginator = new Paginator($qb->getQuery());

        return new PaginatedList(
            items: array_map(fn (TicketEntity $e) => $this->ticketMapper->toDomain($e), iterator_to_array($paginator->getIterator())),
            total: count($paginator),
            page: $pagination->page,
            limit: $pagination->limit,
        );
    }

    public function save(Ticket $ticket): void
    {
        $entity = $this->entityManager->find(TicketEntity::class, $ticket->id()->toString());

        if (null === $entity) {
            $this->entityManager->persist($this->ticketMapper->toEntity($ticket));

            return;
        }

        $this->ticketMapper->updateEntity($entity, $ticket);
    }
}
