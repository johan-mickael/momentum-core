<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Command\CancelTicketCommand;
use App\Application\Ticket\CommandHandler\CancelTicketUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets/{ticketId}', methods: ['DELETE'])]
final class CancelTicketController extends HttpController
{
    public function __construct(
        private readonly CancelTicketUseCase $useCase,
    ) {}

    public function __invoke(string $ticketId): JsonResponse
    {
        ($this->useCase)(
            new CancelTicketCommand($ticketId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
