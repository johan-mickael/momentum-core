<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Query\ListMyTicketsQuery;
use App\Application\Ticket\QueryHandler\ListMyTicketsUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets/me', methods: ['GET'])]
#[OA\Get(
    path: '/api/tickets/me',
    operationId: 'listMyTickets',
    summary: 'List authenticated user\'s own tickets',
    tags: ['Tickets'],
    responses: [
        new OA\Response(response: 200, description: 'My tickets', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/TicketResponse'))),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListMyTicketsController extends HttpController
{
    public function __construct(
        private readonly ListMyTicketsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = ($this->useCase)(
            new ListMyTicketsQuery(),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
