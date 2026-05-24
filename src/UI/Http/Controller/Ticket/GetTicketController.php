<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Query\GetTicketQuery;
use App\Application\Ticket\QueryHandler\GetTicketUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets/{ticketId}', requirements: ['ticketId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
final class GetTicketController extends HttpController
{
    public function __construct(
        private readonly GetTicketUseCase $useCase,
    ) {}

    public function __invoke(string $ticketId): JsonResponse
    {
        $result = ($this->useCase)(
            new GetTicketQuery($ticketId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
