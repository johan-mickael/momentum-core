<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\CancelEventCommand;
use App\Application\Event\CommandHandler\CancelEventUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/cancel', methods: ['PATCH'])]
final class CancelEventController extends HttpController
{
    public function __construct(
        private readonly CancelEventUseCase $useCase,
    ) {}

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new CancelEventCommand($eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
