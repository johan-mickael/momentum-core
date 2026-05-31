<?php

declare(strict_types=1);

namespace UI\Http\Request\Event;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'UpdateTicketCategoryRequest',
    required: ['name', 'priceAmount', 'priceCurrency', 'totalQuantity'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VIP'),
        new OA\Property(property: 'priceAmount', type: 'integer', minimum: 1, description: 'Price in smallest currency unit (cents)', example: 9000),
        new OA\Property(property: 'priceCurrency', type: 'string', minLength: 3, maxLength: 3, description: 'ISO 4217 currency code', example: 'EUR'),
        new OA\Property(property: 'totalQuantity', type: 'integer', minimum: 1, example: 50),
    ],
)]
final class UpdateTicketCategoryRequest
{
    #[Assert\NotBlank(message: 'name is required.')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'price amount is required.')]
    #[Assert\Positive(message: 'price amount must be a positive integer (in cents).')]
    public ?int $priceAmount = null;

    #[Assert\NotBlank(message: 'price currency is required.')]
    #[Assert\Length(exactly: 3, exactMessage: 'price currency must be a 3-letter ISO 4217 code.')]
    public ?string $priceCurrency = null;

    #[Assert\NotBlank(message: 'total quantity is required.')]
    #[Assert\Positive(message: 'total quantity must be at least 1.')]
    public ?int $totalQuantity = null;
}
