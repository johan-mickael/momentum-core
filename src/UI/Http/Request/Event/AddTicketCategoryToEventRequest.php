<?php

declare(strict_types=1);

namespace UI\Http\Request\Event;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    required: ['name', 'priceAmount', 'priceCurrency', 'totalQuantity'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Catégorie A'),
        new OA\Property(property: 'priceAmount', type: 'integer', minimum: 0, description: 'Price in smallest currency unit (cents)', example: 5000),
        new OA\Property(property: 'priceCurrency', type: 'string', minLength: 3, maxLength: 3, description: 'ISO 4217 currency code', example: 'EUR'),
        new OA\Property(property: 'totalQuantity', type: 'integer', minimum: 1, example: 100),
    ],
)]
final class AddTicketCategoryToEventRequest
{
    #[Assert\NotBlank(message: 'name is required.')]
    public ?string $name = null;

    #[Assert\NotNull(message: 'priceAmount is required.')]
    #[Assert\PositiveOrZero(message: 'priceAmount must be zero or a positive integer.')]
    public ?int $priceAmount = null;

    #[Assert\NotBlank(message: 'priceCurrency is required.')]
    #[Assert\Length(exactly: 3, exactMessage: 'priceCurrency must be a 3-letter ISO 4217 code.')]
    public ?string $priceCurrency = null;

    #[Assert\NotNull(message: 'totalQuantity is required.')]
    #[Assert\Positive(message: 'totalQuantity must be a positive integer.')]
    public ?int $totalQuantity = null;
}
