<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Event;

use Symfony\Component\Validator\Constraints as Assert;

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
