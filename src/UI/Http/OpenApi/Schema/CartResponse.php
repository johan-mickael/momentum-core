<?php

declare(strict_types=1);

namespace UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CartItem',
    required: ['categoryId', 'categoryName', 'quantity', 'unitPriceAmount', 'unitPriceCurrency', 'subtotalAmount', 'availableQuantity'],
    properties: [
        new OA\Property(property: 'categoryId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'categoryName', type: 'string', example: 'VIP'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'unitPriceAmount', type: 'integer', description: 'Price in smallest currency unit (cents)', example: 2500),
        new OA\Property(property: 'unitPriceCurrency', type: 'string', example: 'EUR'),
        new OA\Property(property: 'subtotalAmount', type: 'integer', description: 'quantity * unitPriceAmount', example: 5000),
        new OA\Property(property: 'availableQuantity', type: 'integer', description: 'Live remaining stock in this category, for "only N left" UI', example: 12),
    ],
)]
#[OA\Schema(
    schema: 'CartResponse',
    required: ['id', 'status', 'expiresAt', 'items', 'totalAmount', 'currency'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', nullable: true, description: 'null when status is "empty"'),
        new OA\Property(property: 'status', type: 'string', enum: ['empty', 'active', 'checked_out', 'expired'], example: 'active'),
        new OA\Property(property: 'expiresAt', type: 'string', format: 'date-time', nullable: true, description: 'Sliding TTL — refreshed on every mutation. null unless status is "active"'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/CartItem')),
        new OA\Property(property: 'totalAmount', type: 'integer', description: 'Total in smallest currency unit (cents)', example: 5000),
        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
    ],
)]
final class CartResponse
{
}
