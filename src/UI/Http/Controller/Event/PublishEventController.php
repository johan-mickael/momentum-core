<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\PublishEventCommand;
use App\Application\Event\CommandHandler\PublishEventUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/publish', methods: ['PATCH'])]
final class PublishEventController extends HttpController
{
    public function __construct(
        private readonly PublishEventUseCase $useCase,
    ) {}

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new PublishEventCommand($eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
