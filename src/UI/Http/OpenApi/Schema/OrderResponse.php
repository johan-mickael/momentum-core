<?php

declare(strict_types=1);

namespace UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderItem',
    required: ['categoryId', 'ticketId', 'unitPriceAmount', 'unitPriceCurrency'],
    properties: [
        new OA\Property(property: 'categoryId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'ticketId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'unitPriceAmount', type: 'integer', description: 'Price in smallest currency unit (cents)', example: 2500),
        new OA\Property(property: 'unitPriceCurrency', type: 'string', example: 'EUR'),
    ],
)]
#[OA\Schema(
    schema: 'PlaceOrderResponse',
    required: ['id', 'status', 'totalAmount', 'currency', 'expiresAt', 'items'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', example: 'pending_payment'),
        new OA\Property(property: 'totalAmount', type: 'integer', description: 'Total in smallest currency unit (cents)', example: 5000),
        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
        new OA\Property(property: 'expiresAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem')),
    ],
)]
#[OA\Schema(
    schema: 'OrderResponse',
    required: ['id', 'status', 'buyerId', 'eventId', 'totalAmount', 'currency', 'placedAt', 'expiresAt', 'items'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending_payment', 'paid', 'cancelled', 'refunded'], example: 'pending_payment'),
        new OA\Property(property: 'buyerId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'eventId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'totalAmount', type: 'integer', description: 'Total in smallest currency unit (cents)', example: 5000),
        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
        new OA\Property(property: 'placedAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'expiresAt', type: 'string', format: 'date-time'),
        new OA\Property(property: 'paidAt', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'cancelledAt', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem')),
    ],
)]
final class OrderResponse
{
}
