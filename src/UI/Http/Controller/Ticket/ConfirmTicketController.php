<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Command\ConfirmTicketCommand;
use App\Application\Ticket\CommandHandler\ConfirmTicketUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets/{ticketId}/confirm', methods: ['POST'])]
final class ConfirmTicketController extends HttpController
{
    public function __construct(
        private readonly ConfirmTicketUseCase $useCase,
    ) {}

    public function __invoke(string $ticketId): JsonResponse
    {
        ($this->useCase)(
            new ConfirmTicketCommand($ticketId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
