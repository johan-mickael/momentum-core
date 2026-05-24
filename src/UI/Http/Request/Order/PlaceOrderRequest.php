<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Order;

use Symfony\Component\Validator\Constraints as Assert;

final class PlaceOrderRequest
{
    #[Assert\NotBlank(message: 'eventId is required.')]
    #[Assert\Uuid(message: 'eventId must be a valid UUID.')]
    public ?string $eventId = null;

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
