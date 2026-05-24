<?php

declare(strict_types=1);

namespace App\UI\Http\Listener;

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
use App\Domain\Ticket\Exception\CancelTicketForbidden;
use App\Domain\Ticket\Exception\ConfirmTicketForbidden;
use App\Domain\Ticket\Exception\InvalidTicketStatusTransition;
use App\Domain\Ticket\Exception\ListTicketsForbidden;
use App\Domain\Ticket\Exception\MarkTicketAsUsedForbidden;
use App\Domain\Ticket\Exception\TicketNotFound;
use App\Domain\Ticket\Exception\ViewTicketForbidden;
use App\Domain\User\Exception\InvalidCredentials;
use App\Domain\User\Exception\UserAlreadyExists;
use App\Domain\Venue\Exception\CreateVenueForbidden;
use App\Domain\Venue\Exception\DuplicateVenueAddress;
use App\Domain\Venue\Exception\DuplicateVenueName;
use App\Domain\Venue\Exception\GetVenueForbidden;
use App\Domain\Venue\Exception\ListVenuesForbidden;
use App\Domain\Venue\Exception\VenueNotFound;
use InvalidArgumentException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class DomainExceptionListener
{
    private const array MAP = [
        // 401
        InvalidCredentials::class             => Response::HTTP_UNAUTHORIZED,

        // 403
        AccessDeniedException::class          => Response::HTTP_FORBIDDEN,
        ForbiddenAction::class                => Response::HTTP_FORBIDDEN,
        AddTicketCategoryForbidden::class     => Response::HTTP_FORBIDDEN,
        CancelEventForbidden::class           => Response::HTTP_FORBIDDEN,
        CancelTicketForbidden::class          => Response::HTTP_FORBIDDEN,
        ConfirmTicketForbidden::class         => Response::HTTP_FORBIDDEN,
        CreateEventForbidden::class           => Response::HTTP_FORBIDDEN,
        CreateVenueForbidden::class           => Response::HTTP_FORBIDDEN,
        GetVenueForbidden::class              => Response::HTTP_FORBIDDEN,
        ListTicketsForbidden::class           => Response::HTTP_FORBIDDEN,
        ListVenuesForbidden::class            => Response::HTTP_FORBIDDEN,
        MarkTicketAsUsedForbidden::class      => Response::HTTP_FORBIDDEN,
        PublishEventForbidden::class          => Response::HTTP_FORBIDDEN,
        RemoveTicketCategoryForbidden::class  => Response::HTTP_FORBIDDEN,
        UpdateEventForbidden::class           => Response::HTTP_FORBIDDEN,
        UpdateTicketCategoryForbidden::class  => Response::HTTP_FORBIDDEN,
        ViewTicketForbidden::class            => Response::HTTP_FORBIDDEN,

        // 404
        EventNotFound::class                  => Response::HTTP_NOT_FOUND,
        TicketCategoryNotFound::class         => Response::HTTP_NOT_FOUND,
        TicketNotFound::class                 => Response::HTTP_NOT_FOUND,
        VenueNotFound::class                  => Response::HTTP_NOT_FOUND,

        // 409
        CannotAddCategoryToEvent::class                    => Response::HTTP_CONFLICT,
        CannotRemoveTicketCategoryWithReservations::class  => Response::HTTP_CONFLICT,
        CannotReserveTicketInCategory::class               => Response::HTTP_CONFLICT,
        DuplicateTicketCategoryNameForEvent::class         => Response::HTTP_CONFLICT,
        DuplicateVenueAddress::class                       => Response::HTTP_CONFLICT,
        DuplicateVenueName::class                          => Response::HTTP_CONFLICT,
        EventHasNoTicketCategories::class                  => Response::HTTP_CONFLICT,
        InvalidEventStatusTransition::class                => Response::HTTP_CONFLICT,
        InvalidTicketStatusTransition::class               => Response::HTTP_CONFLICT,
        UserAlreadyExists::class                           => Response::HTTP_CONFLICT,

        // 422
        InvalidArgumentException::class      => Response::HTTP_UNPROCESSABLE_ENTITY,
        TicketCategoryCapacityInvalid::class  => Response::HTTP_UNPROCESSABLE_ENTITY,
        TicketCategoryNameEmpty::class        => Response::HTTP_UNPROCESSABLE_ENTITY,
    ];

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = match (true) {
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            isset(self::MAP[$exception::class])          => self::MAP[$exception::class],
            default                                      => Response::HTTP_INTERNAL_SERVER_ERROR,
        };

        $event->setResponse(
            new Response(
                $exception->getMessage(),
                $statusCode,
            ),
        );
    }
}
