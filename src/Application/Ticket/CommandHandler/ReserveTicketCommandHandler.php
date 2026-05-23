<?php

declare(strict_types=1);

namespace App\Application\Ticket\CommandHandler;

use App\Application\Ticket\Command\ReserveTicketCommand;
use App\Application\Ticket\Result\ReserveTicketResult;
use App\Domain\Common\Id\IdGeneratorInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Event\EventRepositoryInterface;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketRepositoryInterface;
use App\Domain\Ticket\ValueObject\TicketId;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

final readonly class ReserveTicketCommandHandler implements ReserveTicketUseCase
{
    public function __construct(
        private EventRepositoryInterface $events,
        private TicketRepositoryInterface $tickets,
        private IdGeneratorInterface $idGenerator,
        private ClockInterface $clock,
        private EntityManagerInterface $entityManager,
    ) {}

    public function __invoke(ReserveTicketCommand $command, UserContext $actor): ReserveTicketResult
    {
        $eventId = EventId::fromString($command->eventId);
        $categoryId = TicketCategoryId::fromString($command->categoryId);

        $event = $this->events->getById($eventId);

        $category = $event->categoryById($categoryId);
        $price = $category->price();

        $event->reserveTicketInCategory($categoryId);

        $ticket = Ticket::reserve(
            id: TicketId::fromString($this->idGenerator->generate()),
            eventId: $eventId,
            categoryId: $categoryId,
            holderId: $actor->id,
            price: $price,
            reservedAt: $this->clock->now(),
        );

        $this->events->save($event);
        $this->tickets->save($ticket);
        $this->entityManager->flush();

        return new ReserveTicketResult(
            id: $ticket->id()->toString(),
            eventId: (string) $ticket->eventId(),
            categoryId: $ticket->categoryId()->toString(),
            holderId: (string) $ticket->holderId(),
            priceAmount: $ticket->price()->amount(),
            priceCurrency: $ticket->price()->currency()->toString(),
            status: $ticket->status()->value,
            reservedAt: $ticket->reservedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
