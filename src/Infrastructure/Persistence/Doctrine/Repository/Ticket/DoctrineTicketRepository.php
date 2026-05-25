<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Ticket;

use App\Domain\Event\ValueObject\EventId;
use App\Domain\Ticket\Exception\TicketNotFound;
use App\Domain\Ticket\Exception\TicketNotFoundByEntryCode;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\ValueObject\EntryCode;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Doctrine\Entity\TicketEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\TicketMapper;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTicketRepository implements TicketRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketMapper $ticketMapper,
    ) {}

    public function findById(TicketId $id): Ticket
    {
        $entity = $this->entityManager->find(TicketEntity::class, $id->toString());

        if ($entity === null) {
            throw TicketNotFound::withId($id);
        }

        return $this->ticketMapper->toDomain($entity);
    }

    public function findByEntryCode(EntryCode $entryCode): Ticket
    {
        $entity = $this->entityManager
            ->getRepository(TicketEntity::class)
            ->findOneBy(['entryCode' => $entryCode->toString()]);

        if ($entity === null) {
            throw TicketNotFoundByEntryCode::withCode($entryCode);
        }

        return $this->ticketMapper->toDomain($entity);
    }

    public function findByEvent(EventId $eventId): array
    {
        $entities = $this->entityManager
            ->getRepository(TicketEntity::class)
            ->findBy(['eventId' => (string) $eventId]);

        return array_map(fn(TicketEntity $e) => $this->ticketMapper->toDomain($e), $entities);
    }

    public function findByHolder(UserId $holderId): array
    {
        $entities = $this->entityManager
            ->getRepository(TicketEntity::class)
            ->findBy(['holderId' => (string) $holderId]);

        return array_map(fn(TicketEntity $e) => $this->ticketMapper->toDomain($e), $entities);
    }

    public function save(Ticket $ticket): void
    {
        $entity = $this->entityManager->find(TicketEntity::class, $ticket->id()->toString());

        if ($entity === null) {
            $this->entityManager->persist($this->ticketMapper->toEntity($ticket));
            return;
        }

        $this->ticketMapper->updateEntity($entity, $ticket);
    }
}
