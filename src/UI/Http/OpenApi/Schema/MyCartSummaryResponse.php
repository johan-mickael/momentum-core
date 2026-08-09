<?php

declare(strict_types=1);

namespace UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MyCartSummary',
    required: ['eventId', 'eventName', 'cart'],
    properties: [
        new OA\Property(property: 'eventId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'eventName', type: 'string', example: 'Summer Fest 2026'),
        new OA\Property(property: 'cart', ref: '#/components/schemas/CartResponse'),
    ],
)]
final class MyCartSummaryResponse
{
}
