<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Command\CheckInTicketCommand;
use App\Application\Ticket\CommandHandler\CheckInTicketUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Ticket\CheckInRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets/check-in', methods: ['POST'])]
final class CheckInController extends HttpController
{
    public function __construct(
        private readonly CheckInTicketUseCase $useCase,
    ) {}

    public function __invoke(
        #[MapRequestPayload] CheckInRequest $request,
    ): JsonResponse {
        ($this->useCase)(
            new CheckInTicketCommand($request->entryCode),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
