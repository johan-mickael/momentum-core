<?php

declare(strict_types=1);

namespace App\UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TicketResponse',
    required: ['id', 'eventId', 'categoryId', 'holderId', 'priceAmount', 'priceCurrency', 'status', 'reservedAt', 'entryCode'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'eventId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'categoryId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'holderId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'priceAmount', type: 'integer', description: 'Price in smallest currency unit (cents)', example: 5000),
        new OA\Property(property: 'priceCurrency', type: 'string', example: 'EUR'),
        new OA\Property(property: 'status', type: 'string', enum: ['reserved', 'confirmed', 'used', 'cancelled'], example: 'reserved'),
        new OA\Property(property: 'reservedAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'entryCode', type: 'string', format: 'uuid', description: 'QR code value for check-in'),
    ],
)]
final class TicketResponse
{
}
