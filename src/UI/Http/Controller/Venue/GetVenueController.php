<?php

declare(strict_types=1);

namespace UI\Http\Controller\Venue;

use Application\Venue\Query\GetVenueByIdQuery;
use Application\Venue\QueryHandler\GetVenueByIdUseCase;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use UI\Http\Controller\HttpController;

#[Route('/venues/{id}', methods: ['GET'])]
#[OA\Get(
    path: '/api/venues/{id}',
    operationId: 'getVenue',
    summary: 'Get a venue by ID',
    tags: ['Venues'],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Venue details', content: new OA\JsonContent(ref: '#/components/schemas/VenueResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 404, description: 'Venue not found', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class GetVenueController extends HttpController
{
    public function __construct(
        private readonly GetVenueByIdUseCase $getVenueByIdUseCase,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $venueResult = ($this->getVenueByIdUseCase)(new GetVenueByIdQuery($id), $this->getUserContext());

        return $this->json($venueResult);
    }
}
