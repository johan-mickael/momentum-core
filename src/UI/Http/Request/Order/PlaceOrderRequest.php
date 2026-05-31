<?php

declare(strict_types=1);

namespace UI\Http\Request\Order;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'PlaceOrderRequest',
    required: ['eventId', 'items'],
    properties: [
        new OA\Property(property: 'eventId', type: 'string', format: 'uuid'),
        new OA\Property(
            property: 'items',
            type: 'array',
            minItems: 1,
            items: new OA\Items(
                type: 'object',
                required: ['categoryId', 'quantity'],
                properties: [
                    new OA\Property(property: 'categoryId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 1),
                ],
            ),
        ),
    ],
)]
final class PlaceOrderRequest
{
    #[Assert\NotBlank(message: 'eventId is required.')]
    #[Assert\Uuid(message: 'eventId must be a valid UUID.')]
    public ?string $eventId = null;

    /** @var array<int, array<string, mixed>>|null */
    #[Assert\NotNull(message: 'items is required.')]
    #[Assert\Count(min: 1, minMessage: 'At least one item is required.')]
    #[Assert\All([
        new Assert\Collection(
            fields: [
                'categoryId' => [
                    new Assert\NotBlank(message: 'categoryId is required.'),
                    new Assert\Uuid(message: 'categoryId must be a valid UUID.'),
                ],
                'quantity' => [
                    new Assert\NotNull(message: 'quantity is required.'),
                    new Assert\Positive(message: 'quantity must be a positive integer.'),
                ],
            ],
        ),
    ])]
    public ?array $items = null;
}
