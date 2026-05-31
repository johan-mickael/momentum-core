<?php

declare(strict_types=1);

namespace UI\Http\Controller\Auth;

use Application\Auth\Query\GetMeQuery;
use Application\Auth\QueryHandler\GetMeUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/auth/me', methods: ['GET'])]
#[OA\Get(
    path: '/api/auth/me',
    operationId: 'getMe',
    summary: 'Get the authenticated user profile',
    tags: ['Auth'],
    responses: [
        new OA\Response(response: 200, description: 'Current user', content: new OA\JsonContent(ref: '#/components/schemas/MeResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class MeController extends HttpController
{
    public function __construct(
        private readonly GetMeUseCase $useCase,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $result = ($this->useCase)(
            new GetMeQuery((string) $this->getUserContext()->id)
        );

        return $this->json($result);
    }
}
