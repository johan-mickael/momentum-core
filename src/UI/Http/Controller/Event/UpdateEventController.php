<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\UpdateEventCommand;
use App\Application\Event\CommandHandler\UpdateEventUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Event\UpdateEventRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}', methods: ['PUT'])]
final class UpdateEventController extends HttpController
{
    public function __construct(
        private readonly UpdateEventUseCase $useCase,
    ) {}

    public function __invoke(
        string $eventId,
        #[MapRequestPayload] UpdateEventRequest $request,
    ): JsonResponse {
        $result = ($this->useCase)(
            UpdateEventCommand::create(
                eventId: $eventId,
                name: $request->name,
                startAt: $request->startAt,
                endAt: $request->endAt,
                venueId: $request->venueId,
                description: $request->description,
                imageUrl: $request->imageUrl,
            ),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
