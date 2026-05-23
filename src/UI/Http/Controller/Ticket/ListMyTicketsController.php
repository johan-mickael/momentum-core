<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Ticket;

use App\Application\Ticket\Query\ListMyTicketsQuery;
use App\Application\Ticket\QueryHandler\ListMyTicketsUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets/me', methods: ['GET'])]
final class ListMyTicketsController extends HttpController
{
    public function __construct(
        private readonly ListMyTicketsUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = ($this->useCase)(
            new ListMyTicketsQuery(),
            $this->getUserContext(),
        );

        return $this->json($result);
    }
}
