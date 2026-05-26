<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Auth;

use App\Application\Auth\Query\GetMeQuery;
use App\Application\Auth\QueryHandler\GetMeUseCase;
use App\UI\Http\Controller\HttpController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/me', methods: ['GET'])]
final class MeController extends HttpController
{
    public function __construct(
        private readonly GetMeUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $result = ($this->useCase)(
            new GetMeQuery((string) $this->getUserContext()->id)
        );

        return $this->json($result);
    }
}
