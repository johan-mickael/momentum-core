<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Command\ReserveTicketCommand;
use App\Application\Ticket\CommandHandler\ReserveTicketUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Ticket\ReserveTicketRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/tickets', methods: ['POST'])]
final class ReserveTicketController extends HttpController
{
    public function __construct(
        private readonly ReserveTicketUseCase $useCase,
    ) {}

    public function __invoke(
        string $eventId,
        #[MapRequestPayload] ReserveTicketRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            new ReserveTicketCommand(
                eventId: $eventId,
                categoryId: $request->categoryId,
            ),
            $this->getUserContext(),
        );

        return $this->json($result, 201);
    }
}
