<?php

declare(strict_types=1);

namespace UI\Http\Request\Venue;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CreateVenueRequest',
    required: ['name', 'street', 'zipCode', 'city', 'country', 'capacity', 'latitude', 'longitude'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Salle Pleyel'),
        new OA\Property(property: 'street', type: 'string', example: '252 Rue du Faubourg Saint-Honoré'),
        new OA\Property(property: 'zipCode', type: 'string', example: '75008'),
        new OA\Property(property: 'city', type: 'string', example: 'Paris'),
        new OA\Property(property: 'country', type: 'string', example: 'France'),
        new OA\Property(property: 'capacity', type: 'integer', minimum: 1, example: 1900),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 48.8757),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 2.3089),
    ],
)]
final class CreateVenueRequest
{
    #[Assert\NotBlank(message: 'name is required.')]
    public ?string $name = '';

    #[Assert\NotBlank(message: 'street is required.')]
    public ?string $street = '';

    #[Assert\NotBlank(message: 'zip code is required.')]
    public ?string $zipCode;

    #[Assert\NotBlank(message: 'city is required.')]
    public ?string $city = '';

    #[Assert\NotBlank(message: 'country is required.')]
    public ?string $country = '';

    #[Assert\NotBlank(message: 'capacity is required.')]
    public ?int $capacity = 0;

    #[Assert\NotBlank(message: 'latitude is required.')]
    public ?float $latitude = null;

    #[Assert\NotBlank(message: 'longitude is required.')]
    public ?float $longitude = null;
}
