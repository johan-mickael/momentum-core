<?php

declare(strict_types=1);

namespace App\UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'VenueResponse',
    required: ['id', 'name', 'street', 'city', 'country', 'capacity'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Salle Pleyel'),
        new OA\Property(property: 'street', type: 'string', example: '252 Rue du Faubourg Saint-Honoré'),
        new OA\Property(property: 'city', type: 'string', example: 'Paris'),
        new OA\Property(property: 'country', type: 'string', example: 'France'),
        new OA\Property(property: 'zipCode', type: 'string', nullable: true, example: '75008'),
        new OA\Property(property: 'capacity', type: 'integer', example: 1900),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true, example: 48.8757),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true, example: 2.3089),
    ],
)]
final class VenueResponse
{
}
