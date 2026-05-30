<?php

declare(strict_types=1);

namespace App\UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'VenueSummary',
    required: ['id', 'name', 'street', 'city', 'zipCode', 'country'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Salle Pleyel'),
        new OA\Property(property: 'street', type: 'string', example: '252 Rue du Faubourg Saint-Honoré'),
        new OA\Property(property: 'city', type: 'string', example: 'Paris'),
        new OA\Property(property: 'zipCode', type: 'string', example: '75008'),
        new OA\Property(property: 'country', type: 'string', example: 'France'),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true, example: 48.8757),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true, example: 2.3089),
    ],
)]
#[OA\Schema(
    schema: 'EventResponse',
    required: ['id', 'name', 'venue', 'slug', 'startAt', 'endAt', 'status', 'createdAt'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Symphonie 2026'),
        new OA\Property(property: 'venue', ref: '#/components/schemas/VenueSummary'),
        new OA\Property(property: 'slug', type: 'string', example: 'symphonie-2026'),
        new OA\Property(property: 'startAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'endAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'status', type: 'string', enum: ['DRAFT', 'PUBLISHED', 'CANCELLED', 'ENDED'], example: 'PUBLISHED'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'imageUrl', type: 'string', format: 'uri', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'CreateEventResponse',
    required: ['id', 'name', 'venueId', 'slug', 'startAt', 'endAt', 'status', 'createdAt'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Symphonie 2026'),
        new OA\Property(property: 'venueId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'slug', type: 'string', example: 'symphonie-2026'),
        new OA\Property(property: 'startAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'endAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'status', type: 'string', enum: ['DRAFT', 'PUBLISHED', 'CANCELLED', 'ENDED'], example: 'DRAFT'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'imageUrl', type: 'string', format: 'uri', nullable: true),
    ],
)]
final class EventResponse
{
}
