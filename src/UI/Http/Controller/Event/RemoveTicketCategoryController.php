<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Command\RemoveTicketCategoryCommand;
use App\Application\Event\CommandHandler\RemoveTicketCategoryUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/ticket-categories/{categoryId}', methods: ['DELETE'])]
final class RemoveTicketCategoryController extends HttpController
{
    public function __construct(
        private readonly RemoveTicketCategoryUseCase $useCase,
    ) {}

    public function __invoke(string $eventId, string $categoryId): JsonResponse
    {
        ($this->useCase)(
            new RemoveTicketCategoryCommand($eventId, $categoryId),
            $this->getUserContext(),
        );

        return $this->json(null, 204);
    }
}
