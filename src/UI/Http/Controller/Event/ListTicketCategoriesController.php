<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Query\ListTicketCategoriesQuery;
use App\Application\Event\QueryHandler\ListTicketCategoriesUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/ticket-categories', methods: ['GET'])]
final class ListTicketCategoriesController extends HttpController
{
    public function __construct(
        private readonly ListTicketCategoriesUseCase $useCase,
    ) {}

    public function __invoke(string $eventId): JsonResponse
    {
        $result = ($this->useCase)(
            new ListTicketCategoriesQuery($eventId),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
