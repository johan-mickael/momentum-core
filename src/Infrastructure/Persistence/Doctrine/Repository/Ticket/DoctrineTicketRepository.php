<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Ticket;

use App\Domain\Common\ValueObject\PaginatedList;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Ticket\Exception\TicketNotFound;
use App\Domain\Ticket\Exception\TicketNotFoundByEntryCode;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\TicketStatus;
use App\Domain\Ticket\ValueObject\EntryCode;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Doctrine\Entity\TicketEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\TicketMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

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
