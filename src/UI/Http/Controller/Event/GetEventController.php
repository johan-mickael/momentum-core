<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Query\GetEventByIdQuery;
use App\Application\Event\QueryHandler\GetEventByIdUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}', requirements: ['eventId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
final class GetEventController extends HttpController
{
    public function __construct(
        private readonly GetEventByIdUseCase $useCase,
    ) {}

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new GetEventByIdQuery($eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
