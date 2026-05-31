<?php

declare(strict_types=1);

namespace Tests\Unit\UI\Http\Listener;

use Domain\Common\Exception\InvalidPageLimit;
use Domain\Common\Exception\InvalidPageNumber;
use Domain\Common\Security\Authorization\Exception\ForbiddenAction;
use Domain\Event\EventStatus;
use Domain\Event\Exception\AddTicketCategoryForbidden;
use Domain\Event\Exception\CancelEventForbidden;
use Domain\Event\Exception\CannotAddCategoryToEvent;
use Domain\Event\Exception\CannotRemoveTicketCategoryWithReservations;
use Domain\Event\Exception\CannotReserveTicketInCategory;
use Domain\Event\Exception\CreateEventForbidden;
use Domain\Event\Exception\DuplicateTicketCategoryNameForEvent;
use Domain\Event\Exception\EventHasNoTicketCategories;
use Domain\Event\Exception\EventNotFound;
use Domain\Event\Exception\InvalidEventStatusTransition;
use Domain\Event\Exception\PublishEventForbidden;
use Domain\Event\Exception\RemoveTicketCategoryForbidden;
use Domain\Event\Exception\TicketCategoryCapacityInvalid;
use Domain\Event\Exception\TicketCategoryNameEmpty;
use Domain\Event\Exception\TicketCategoryNotFound;
use Domain\Event\Exception\UpdateEventForbidden;
use Domain\Event\Exception\UpdateTicketCategoryForbidden;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\Ticket\Exception\CancelTicketForbidden;
use Domain\Ticket\Exception\InvalidTicketStatusTransition;
use Domain\Ticket\Exception\TicketNotFound;
use Domain\Ticket\Exception\ViewTicketForbidden;
use Domain\Ticket\TicketStatus;
use Domain\Ticket\ValueObject\TicketId;
use Domain\User\Exception\InvalidCredentials;
use Domain\User\Exception\UserAlreadyExists;
use Domain\Venue\Exception\CreateVenueForbidden;
use Domain\Venue\Exception\DuplicateVenueName;
use Domain\Venue\Exception\VenueNotFound;
use Domain\Venue\ValueObject\VenueId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use UI\Http\Listener\DomainExceptionListener;

final class DomainExceptionListenerTest extends TestCase
{
    private const string EVENT_ID = '123e4567-e89b-12d3-a456-426614174000';
    private const string CATEGORY_ID = '223e4567-e89b-12d3-a456-426614174001';
    private const string TICKET_ID = '323e4567-e89b-12d3-a456-426614174002';
    private const string VENUE_ID = '423e4567-e89b-12d3-a456-426614174003';

    private DomainExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new DomainExceptionListener();
    }

    #[DataProvider('provideMappedExceptions')]
    public function testMappedExceptionReturnsCorrectStatusCode(
        \Throwable $exception,
        int $expectedStatus,
    ): void {
        $event = $this->makeExceptionEvent($exception);

        $this->listener->onKernelException($event);

        self::assertSame($expectedStatus, $event->getResponse()->getStatusCode());
    }

    public static function provideMappedExceptions(): array
    {
        $eventId = EventId::fromString('123e4567-e89b-12d3-a456-426614174000');
        $categoryId = TicketCategoryId::fromString('223e4567-e89b-12d3-a456-426614174001');
        $ticketId = TicketId::fromString('323e4567-e89b-12d3-a456-426614174002');
        $venueId = VenueId::fromString('423e4567-e89b-12d3-a456-426614174003');

        return [
            // 400
            '400 InvalidPageNumber' => [new InvalidPageNumber(0), Response::HTTP_BAD_REQUEST],
            '400 InvalidPageLimit' => [new InvalidPageLimit(0, 100), Response::HTTP_BAD_REQUEST],

            // 401
            '401 InvalidCredentials' => [InvalidCredentials::create(), Response::HTTP_UNAUTHORIZED],

            // 403 — pre-existing
            '403 ForbiddenAction' => [new ForbiddenAction(), Response::HTTP_FORBIDDEN],
            '403 AddTicketCategoryForbidden' => [AddTicketCategoryForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 CreateEventForbidden' => [new CreateEventForbidden(), Response::HTTP_FORBIDDEN],
            '403 CreateVenueForbidden' => [new CreateVenueForbidden(), Response::HTTP_FORBIDDEN],
            '403 CancelTicketForbidden' => [CancelTicketForbidden::forTicket($ticketId), Response::HTTP_FORBIDDEN],
            '403 ViewTicketForbidden' => [ViewTicketForbidden::forTicket($ticketId), Response::HTTP_FORBIDDEN],

            // 403 — Phase 1 new
            '403 PublishEventForbidden' => [PublishEventForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 CancelEventForbidden' => [CancelEventForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 UpdateEventForbidden' => [UpdateEventForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 UpdateTicketCategoryForbidden' => [UpdateTicketCategoryForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 RemoveTicketCategoryForbidden' => [RemoveTicketCategoryForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],

            // 404
            '404 EventNotFound' => [EventNotFound::forId($eventId), Response::HTTP_NOT_FOUND],
            '404 TicketCategoryNotFound' => [TicketCategoryNotFound::withId($categoryId), Response::HTTP_NOT_FOUND],
            '404 TicketNotFound' => [TicketNotFound::withId($ticketId), Response::HTTP_NOT_FOUND],
            '404 VenueNotFound' => [VenueNotFound::forId($venueId), Response::HTTP_NOT_FOUND],

            // 409 — pre-existing
            '409 CannotAddCategoryToEvent' => [CannotAddCategoryToEvent::eventIsNotActive(EventStatus::CANCELLED), Response::HTTP_CONFLICT],
            '409 CannotReserveTicketInCategory' => [CannotReserveTicketInCategory::noAvailability('VIP'), Response::HTTP_CONFLICT],
            '409 DuplicateVenueName' => [DuplicateVenueName::for('Arena'), Response::HTTP_CONFLICT],
            '409 InvalidEventStatusTransition (publish)' => [InvalidEventStatusTransition::cannotPublish(EventStatus::PUBLISHED), Response::HTTP_CONFLICT],
            '409 InvalidEventStatusTransition (cancel)' => [InvalidEventStatusTransition::cannotCancel(EventStatus::CANCELLED), Response::HTTP_CONFLICT],
            '409 InvalidTicketStatusTransition' => [InvalidTicketStatusTransition::cannotConfirm(TicketStatus::RESERVED), Response::HTTP_CONFLICT],
            '409 UserAlreadyExists' => [UserAlreadyExists::create('test@test.com'), Response::HTTP_CONFLICT],

            // 409 — Phase 1 new
            '409 EventHasNoTicketCategories' => [EventHasNoTicketCategories::create(), Response::HTTP_CONFLICT],
            '409 CannotRemoveWithReservations' => [CannotRemoveTicketCategoryWithReservations::forCategory($categoryId), Response::HTTP_CONFLICT],
            '409 DuplicateTicketCategoryNameForEvent' => [DuplicateTicketCategoryNameForEvent::withName('VIP'), Response::HTTP_CONFLICT],

            // 422 — pre-existing
            '422 InvalidArgumentException' => [new \InvalidArgumentException('bad input'), Response::HTTP_UNPROCESSABLE_ENTITY],

            // 422 — Phase 1 new
            '422 TicketCategoryCapacityInvalid' => [TicketCategoryCapacityInvalid::create(0), Response::HTTP_UNPROCESSABLE_ENTITY],
            '422 TicketCategoryNameEmpty' => [TicketCategoryNameEmpty::create(), Response::HTTP_UNPROCESSABLE_ENTITY],
        ];
    }

    public function testUnmappedExceptionReturns500(): void
    {
        $event = $this->makeExceptionEvent(new \RuntimeException('Something went wrong'));

        $this->listener->onKernelException($event);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $event->getResponse()->getStatusCode());
    }

    public function testHttpExceptionUsesItsOwnStatusCodeRegardlessOfMap(): void
    {
        $event = $this->makeExceptionEvent(new NotFoundHttpException('Route not found'));

        $this->listener->onKernelException($event);

        self::assertSame(Response::HTTP_NOT_FOUND, $event->getResponse()->getStatusCode());
    }

    public function testResponseBodyContainsExceptionMessage(): void
    {
        $event = $this->makeExceptionEvent(EventHasNoTicketCategories::create());

        $this->listener->onKernelException($event);

        self::assertStringContainsString(
            'Cannot publish an event that has no ticket categories.',
            $event->getResponse()->getContent(),
        );
    }

    private function makeExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
