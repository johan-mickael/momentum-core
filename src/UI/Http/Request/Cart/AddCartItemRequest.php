<?php

declare(strict_types=1);

namespace UI\Http\Request\Cart;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'AddCartItemRequest',
    required: ['categoryId', 'quantity'],
    properties: [
        new OA\Property(property: 'categoryId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 20, example: 1),
    ],
)]
final class AddCartItemRequest
{
    #[Assert\NotBlank(message: 'categoryId is required.')]
    #[Assert\Uuid(message: 'categoryId must be a valid UUID.')]
    public ?string $categoryId = null;

    #[Assert\NotNull(message: 'quantity is required.')]
    #[Assert\Range(min: 1, max: 20, notInRangeMessage: 'quantity must be between {{ min }} and {{ max }}.')]
    public ?int $quantity = null;
}
