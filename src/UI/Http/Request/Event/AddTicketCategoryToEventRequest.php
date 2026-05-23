<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Event;

use Symfony\Component\Validator\Constraints as Assert;

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
