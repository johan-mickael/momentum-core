<?php

declare(strict_types=1);

namespace UI\Http\Request\Cart;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'UpdateCartItemQuantityRequest',
    required: ['quantity'],
    properties: [
        new OA\Property(property: 'quantity', type: 'integer', minimum: 0, maximum: 20, description: '0 removes the item', example: 2),
    ],
)]
final class UpdateCartItemQuantityRequest
{
    #[Assert\NotNull(message: 'quantity is required.')]
    #[Assert\Range(min: 0, max: 20, notInRangeMessage: 'quantity must be between {{ min }} and {{ max }}.')]
    public ?int $quantity = null;
}
