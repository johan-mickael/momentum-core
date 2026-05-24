<?php

declare(strict_types=1);

namespace App\Tests\Unit\UI\Http\Listener;

use App\Domain\Common\Security\Authorization\Exception\ForbiddenAction;
use App\Domain\Event\Exception\AddTicketCategoryForbidden;
use App\Domain\Event\Exception\CannotAddCategoryToEvent;
use App\Domain\Event\Exception\CannotRemoveTicketCategoryWithReservations;
use App\Domain\Event\Exception\CannotReserveTicketInCategory;
use App\Domain\Event\Exception\CancelEventForbidden;
use App\Domain\Event\Exception\CreateEventForbidden;
use App\Domain\Event\Exception\DuplicateTicketCategoryNameForEvent;
use App\Domain\Event\Exception\EventHasNoTicketCategories;
use App\Domain\Event\Exception\EventNotFound;
use App\Domain\Event\Exception\InvalidEventStatusTransition;
use App\Domain\Event\Exception\PublishEventForbidden;
use App\Domain\Event\Exception\RemoveTicketCategoryForbidden;
use App\Domain\Event\Exception\TicketCategoryCapacityInvalid;
use App\Domain\Event\Exception\TicketCategoryNameEmpty;
use App\Domain\Event\Exception\TicketCategoryNotFound;
use App\Domain\Event\Exception\UpdateEventForbidden;
use App\Domain\Event\Exception\UpdateTicketCategoryForbidden;
use App\Domain\Event\EventStatus;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Ticket\Exception\CancelTicketForbidden;
use App\Domain\Ticket\Exception\InvalidTicketStatusTransition;
use App\Domain\Ticket\Exception\TicketNotFound;
use App\Domain\Ticket\Exception\ViewTicketForbidden;
use App\Domain\Ticket\TicketStatus;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\Exception\InvalidCredentials;
use App\Domain\User\Exception\UserAlreadyExists;
use App\Domain\Venue\Exception\CreateVenueForbidden;
use App\Domain\Venue\Exception\DuplicateVenueName;
use App\Domain\Venue\Exception\VenueNotFound;
use App\Domain\Venue\ValueObject\VenueId;
use App\UI\Http\Listener\DomainExceptionListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class DomainExceptionListenerTest extends TestCase
{
    private const string EVENT_ID    = '123e4567-e89b-12d3-a456-426614174000';
    private const string CATEGORY_ID = '223e4567-e89b-12d3-a456-426614174001';
    private const string TICKET_ID   = '323e4567-e89b-12d3-a456-426614174002';
    private const string VENUE_ID    = '423e4567-e89b-12d3-a456-426614174003';

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
        $eventId    = EventId::fromString('123e4567-e89b-12d3-a456-426614174000');
        $categoryId = TicketCategoryId::fromString('223e4567-e89b-12d3-a456-426614174001');
        $ticketId   = TicketId::fromString('323e4567-e89b-12d3-a456-426614174002');
        $venueId    = VenueId::fromString('423e4567-e89b-12d3-a456-426614174003');

        return [
            // 401
            '401 InvalidCredentials'                       => [InvalidCredentials::create(), Response::HTTP_UNAUTHORIZED],

            // 403 — pre-existing
            '403 ForbiddenAction'                          => [new ForbiddenAction(), Response::HTTP_FORBIDDEN],
            '403 AddTicketCategoryForbidden'               => [AddTicketCategoryForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 CreateEventForbidden'                     => [new CreateEventForbidden(), Response::HTTP_FORBIDDEN],
            '403 CreateVenueForbidden'                     => [new CreateVenueForbidden(), Response::HTTP_FORBIDDEN],
            '403 CancelTicketForbidden'                    => [CancelTicketForbidden::forTicket($ticketId), Response::HTTP_FORBIDDEN],
            '403 ViewTicketForbidden'                      => [ViewTicketForbidden::forTicket($ticketId), Response::HTTP_FORBIDDEN],

            // 403 — Phase 1 new
            '403 PublishEventForbidden'                    => [PublishEventForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 CancelEventForbidden'                     => [CancelEventForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 UpdateEventForbidden'                     => [UpdateEventForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 UpdateTicketCategoryForbidden'            => [UpdateTicketCategoryForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],
            '403 RemoveTicketCategoryForbidden'            => [RemoveTicketCategoryForbidden::forEvent($eventId), Response::HTTP_FORBIDDEN],

            // 404
            '404 EventNotFound'                            => [EventNotFound::forId($eventId), Response::HTTP_NOT_FOUND],
            '404 TicketCategoryNotFound'                   => [TicketCategoryNotFound::withId($categoryId), Response::HTTP_NOT_FOUND],
            '404 TicketNotFound'                           => [TicketNotFound::withId($ticketId), Response::HTTP_NOT_FOUND],
            '404 VenueNotFound'                            => [VenueNotFound::forId($venueId), Response::HTTP_NOT_FOUND],

            // 409 — pre-existing
            '409 CannotAddCategoryToEvent'                 => [CannotAddCategoryToEvent::eventIsNotActive(EventStatus::CANCELLED), Response::HTTP_CONFLICT],
            '409 CannotReserveTicketInCategory'            => [CannotReserveTicketInCategory::noAvailability('VIP'), Response::HTTP_CONFLICT],
            '409 DuplicateVenueName'                       => [DuplicateVenueName::for('Arena'), Response::HTTP_CONFLICT],
            '409 InvalidEventStatusTransition (publish)'   => [InvalidEventStatusTransition::cannotPublish(EventStatus::PUBLISHED), Response::HTTP_CONFLICT],
            '409 InvalidEventStatusTransition (cancel)'    => [InvalidEventStatusTransition::cannotCancel(EventStatus::CANCELLED), Response::HTTP_CONFLICT],
            '409 InvalidTicketStatusTransition'            => [InvalidTicketStatusTransition::cannotConfirm(TicketStatus::RESERVED), Response::HTTP_CONFLICT],
            '409 UserAlreadyExists'                        => [UserAlreadyExists::create('test@test.com'), Response::HTTP_CONFLICT],

            // 409 — Phase 1 new
            '409 EventHasNoTicketCategories'               => [EventHasNoTicketCategories::create(), Response::HTTP_CONFLICT],
            '409 CannotRemoveWithReservations'             => [CannotRemoveTicketCategoryWithReservations::forCategory($categoryId), Response::HTTP_CONFLICT],
            '409 DuplicateTicketCategoryNameForEvent'      => [DuplicateTicketCategoryNameForEvent::withName('VIP'), Response::HTTP_CONFLICT],

            // 422 — pre-existing
            '422 InvalidArgumentException'                 => [new \InvalidArgumentException('bad input'), Response::HTTP_UNPROCESSABLE_ENTITY],

            // 422 — Phase 1 new
            '422 TicketCategoryCapacityInvalid'            => [TicketCategoryCapacityInvalid::create(0), Response::HTTP_UNPROCESSABLE_ENTITY],
            '422 TicketCategoryNameEmpty'                  => [TicketCategoryNameEmpty::create(), Response::HTTP_UNPROCESSABLE_ENTITY],
        ];
    }

    public function testUnmappedExceptionReturns500(): void
    {
        $event = $this->makeExceptionEvent(new RuntimeException('Something went wrong'));

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
