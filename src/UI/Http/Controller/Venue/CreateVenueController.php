<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Venue;

use App\Application\Venue\Command\CreateVenueCommand;
use App\Application\Venue\CommandHandler\CreateVenueUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Venue\CreateVenueRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/venues', methods: ['POST'])]
#[OA\Post(
    path: '/api/venues',
    operationId: 'createVenue',
    summary: 'Create a new venue',
    tags: ['Venues'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CreateVenueRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Venue created', content: new OA\JsonContent(ref: '#/components/schemas/VenueResponse')),
        new OA\Response(response: 401, description: 'Not authenticated', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 403, description: 'STAFF role required', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 409, description: 'Duplicate venue name or address', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
        new OA\Response(response: 422, description: 'Validation error', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(ref: '#/components/schemas/ErrorResponse'))),
    ],
)]
final class CreateVenueController extends HttpController
{
    public function __construct(
        private readonly CreateVenueUseCase $createVenueUseCase,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload]
        CreateVenueRequest $dto,
    ): JsonResponse {
        $createVenueCommand = new CreateVenueCommand(
            name: $dto->name,
            street: $dto->street,
            zipCode: $dto->zipCode,
            city: $dto->city,
            country: $dto->country,
            capacity: $dto->capacity,
            latitude: $dto->latitude,
            longitude: $dto->longitude,
        );

        $createVenueResult = ($this->createVenueUseCase)($createVenueCommand, $this->getUserContext());

        return $this->json(
            $createVenueResult,
            Response::HTTP_CREATED,
        );
    }
}
