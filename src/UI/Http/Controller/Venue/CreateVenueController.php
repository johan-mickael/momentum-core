<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Venue;

use App\Application\Venue\Command\CreateVenueCommand;
use App\Application\Venue\CommandHandler\CreateVenueUseCase;
use App\UI\Http\Controller\HttpController;
use App\UI\Http\Request\Venue\CreateVenueRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/venues', methods: ['POST'])]
final
class CreateVenueController extends HttpController {
    public
    function __construct(
        private readonly CreateVenueUseCase $createVenueUseCase,
    ) {}

    public
    function __invoke(
        #[MapRequestPayload] CreateVenueRequest $dto,
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
