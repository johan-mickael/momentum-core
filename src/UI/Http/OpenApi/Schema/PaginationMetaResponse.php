<?php

declare(strict_types=1);

namespace App\UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginationMeta',
    required: ['page', 'limit', 'total', 'totalPages'],
    properties: [
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'limit', type: 'integer', example: 20),
        new OA\Property(property: 'total', type: 'integer', example: 150),
        new OA\Property(property: 'totalPages', type: 'integer', example: 8),
    ],
)]
#[OA\Schema(
    schema: 'PaginatedEventsResponse',
    required: ['data', 'pagination'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/EventResponse')),
        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
    ],
)]
#[OA\Schema(
    schema: 'PaginatedVenuesResponse',
    required: ['data', 'pagination'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/VenueResponse')),
        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
    ],
)]
#[OA\Schema(
    schema: 'PaginatedOrdersResponse',
    required: ['data', 'pagination'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderResponse')),
        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
    ],
)]
#[OA\Schema(
    schema: 'PaginatedTicketsResponse',
    required: ['data', 'pagination'],
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TicketResponse')),
        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
    ],
)]
final class PaginationMetaResponse {}
