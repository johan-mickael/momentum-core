<?php

declare(strict_types=1);

namespace App\UI\Http\OpenApi\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TicketCategoryResponse',
    required: ['id', 'name', 'priceAmount', 'priceCurrency', 'totalQuantity', 'availableQuantity'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string', example: 'Catégorie A'),
        new OA\Property(property: 'priceAmount', type: 'integer', description: 'Price in smallest currency unit (cents)', example: 5000),
        new OA\Property(property: 'priceCurrency', type: 'string', example: 'EUR'),
        new OA\Property(property: 'totalQuantity', type: 'integer', example: 100),
        new OA\Property(property: 'availableQuantity', type: 'integer', example: 87),
    ],
)]
final class TicketCategoryResponse
{
}
