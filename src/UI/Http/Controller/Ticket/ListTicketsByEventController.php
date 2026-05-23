<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Query\ListTicketsByEventQuery;
use App\Application\Ticket\QueryHandler\ListTicketsByEventUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/tickets', methods: ['GET'])]
final class ListTicketsByEventController extends HttpController
{
    public function __construct(
        private readonly ListTicketsByEventUseCase $useCase,
    ) {}

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new ListTicketsByEventQuery($eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
