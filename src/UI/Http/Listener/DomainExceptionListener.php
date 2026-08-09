<?php

declare(strict_types=1);

namespace UI\Http\Listener;

use Application\Order\Exception\WebhookSignatureInvalid;
use Domain\Common\Exception\InvalidPageLimit;
use Domain\Common\Exception\InvalidPageNumber;
use Domain\Common\Security\Authorization\Exception\ForbiddenAction;
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
use Domain\Order\Exception\CancelOrderForbidden;
use Domain\Order\Exception\CannotPayCancelledOrder;
use Domain\Order\Exception\CheckoutForbidden;
use Domain\Order\Exception\OrderAlreadyPaid;
use Domain\Order\Exception\OrderNotFound;
use Domain\Order\Exception\OrderNotPendingPayment;
use Domain\Order\Exception\PlaceOrderForbidden;
use Domain\Order\Exception\ViewOrderForbidden;
use Domain\Ticket\Exception\CancelTicketForbidden;
use Domain\Ticket\Exception\ConfirmTicketForbidden;
use Domain\Ticket\Exception\InvalidTicketStatusTransition;
use Domain\Ticket\Exception\ListTicketsForbidden;
use Domain\Ticket\Exception\MarkTicketAsUsedForbidden;
use Domain\Ticket\Exception\TicketNotFound;
use Domain\Ticket\Exception\ViewTicketForbidden;
use Domain\User\Exception\InvalidCredentials;
use Domain\User\Exception\UserAlreadyExists;
use Domain\User\Exception\UserNotFound;
use Domain\Venue\Exception\CreateVenueForbidden;
use Domain\Venue\Exception\DuplicateVenueAddress;
use Domain\Venue\Exception\DuplicateVenueName;
use Domain\Venue\Exception\GetVenueForbidden;
use Domain\Venue\Exception\ListVenuesForbidden;
use Domain\Venue\Exception\VenueNotFound;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use UI\Http\Exception\UnauthenticatedRequest;

final class DomainExceptionListener
{
    private const array MAP = [
        // 400
        WebhookSignatureInvalid::class => Response::HTTP_BAD_REQUEST,
        InvalidPageNumber::class => Response::HTTP_BAD_REQUEST,
        InvalidPageLimit::class => Response::HTTP_BAD_REQUEST,

        // 401
        InvalidCredentials::class => Response::HTTP_UNAUTHORIZED,
        UnauthenticatedRequest::class => Response::HTTP_UNAUTHORIZED,

        // 403
        AccessDeniedException::class => Response::HTTP_FORBIDDEN,
        ForbiddenAction::class => Response::HTTP_FORBIDDEN,
        AddTicketCategoryForbidden::class => Response::HTTP_FORBIDDEN,
        CancelEventForbidden::class => Response::HTTP_FORBIDDEN,
        CancelTicketForbidden::class => Response::HTTP_FORBIDDEN,
        ConfirmTicketForbidden::class => Response::HTTP_FORBIDDEN,
        CreateEventForbidden::class => Response::HTTP_FORBIDDEN,
        CreateVenueForbidden::class => Response::HTTP_FORBIDDEN,
        GetVenueForbidden::class => Response::HTTP_FORBIDDEN,
        ListTicketsForbidden::class => Response::HTTP_FORBIDDEN,
        ListVenuesForbidden::class => Response::HTTP_FORBIDDEN,
        MarkTicketAsUsedForbidden::class => Response::HTTP_FORBIDDEN,
        CancelOrderForbidden::class => Response::HTTP_FORBIDDEN,
        CheckoutForbidden::class => Response::HTTP_FORBIDDEN,
        PlaceOrderForbidden::class => Response::HTTP_FORBIDDEN,
        ViewOrderForbidden::class => Response::HTTP_FORBIDDEN,
        PublishEventForbidden::class => Response::HTTP_FORBIDDEN,
        RemoveTicketCategoryForbidden::class => Response::HTTP_FORBIDDEN,
        UpdateEventForbidden::class => Response::HTTP_FORBIDDEN,
        UpdateTicketCategoryForbidden::class => Response::HTTP_FORBIDDEN,
        ViewTicketForbidden::class => Response::HTTP_FORBIDDEN,

        // 404
        EventNotFound::class => Response::HTTP_NOT_FOUND,
        OrderNotFound::class => Response::HTTP_NOT_FOUND,
        TicketCategoryNotFound::class => Response::HTTP_NOT_FOUND,
        TicketNotFound::class => Response::HTTP_NOT_FOUND,
        UserNotFound::class => Response::HTTP_NOT_FOUND,
        VenueNotFound::class => Response::HTTP_NOT_FOUND,

        // 409
        CannotAddCategoryToEvent::class => Response::HTTP_CONFLICT,
        CannotPayCancelledOrder::class => Response::HTTP_CONFLICT,
        CannotRemoveTicketCategoryWithReservations::class => Response::HTTP_CONFLICT,
        CannotReserveTicketInCategory::class => Response::HTTP_CONFLICT,
        DuplicateTicketCategoryNameForEvent::class => Response::HTTP_CONFLICT,
        DuplicateVenueAddress::class => Response::HTTP_CONFLICT,
        DuplicateVenueName::class => Response::HTTP_CONFLICT,
        EventHasNoTicketCategories::class => Response::HTTP_CONFLICT,
        InvalidEventStatusTransition::class => Response::HTTP_CONFLICT,
        InvalidTicketStatusTransition::class => Response::HTTP_CONFLICT,
        OrderAlreadyPaid::class => Response::HTTP_CONFLICT,
        OrderNotPendingPayment::class => Response::HTTP_CONFLICT,
        UserAlreadyExists::class => Response::HTTP_CONFLICT,

        // 422
        \InvalidArgumentException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        TicketCategoryCapacityInvalid::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        TicketCategoryNameEmpty::class => Response::HTTP_UNPROCESSABLE_ENTITY,
    ];

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = match (true) {
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            isset(self::MAP[$exception::class]) => self::MAP[$exception::class],
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        $event->setResponse(
            new Response(
                $exception->getMessage(),
                $statusCode,
            ),
        );
    }
}
