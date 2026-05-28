<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Venue;

use App\Application\Venue\Query\ListVenuesQuery;
use App\Application\Venue\QueryHandler\ListVenuesUseCase;
use App\UI\Http\Controller\HttpController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/venues', methods: ['GET'])]
#[OA\Get(
    path: '/api/venues',
    operationId: 'listVenues',
    summary: 'List all venues',
    tags: ['Venues'],
    responses: [
        new OA\Response(response: 200, description: 'List of venues', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/VenueResponse'))),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class ListVenuesController extends HttpController
{
    public function __construct(
        private readonly ListVenuesUseCase $listVenuesUseCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $venuesResult = ($this->listVenuesUseCase)(new ListVenuesQuery(), $this->getUserContext());

        return $this->json($venuesResult);
    }
}
