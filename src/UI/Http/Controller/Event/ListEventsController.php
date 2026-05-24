<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Event;

use App\Application\Event\Query\ListEventsQuery;
use App\Application\Event\QueryHandler\ListEventsUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events', methods: ['GET'])]
final class ListEventsController extends HttpController
{
    public function __construct(
        private readonly ListEventsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = ($this->useCase)(
            new ListEventsQuery(),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
