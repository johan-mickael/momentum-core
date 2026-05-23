<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Command\MarkTicketAsUsedCommand;
use App\Application\Ticket\CommandHandler\MarkTicketAsUsedUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets/{ticketId}/use', methods: ['POST'])]
final class MarkTicketAsUsedController extends HttpController
{
    public function __construct(
        private readonly MarkTicketAsUsedUseCase $useCase,
    ) {}

    public function __invoke(string $ticketId): JsonResponse
    {
        ($this->useCase)(
            new MarkTicketAsUsedCommand($ticketId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
