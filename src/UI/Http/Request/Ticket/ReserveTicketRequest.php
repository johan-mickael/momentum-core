<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Ticket;

use Symfony\Component\Validator\Constraints as Assert;

final class ReserveTicketRequest
{
    #[Assert\NotBlank(message: 'categoryId is required.')]
    #[Assert\Uuid(message: 'categoryId must be a valid UUID.')]
    public ?string $categoryId = null;
}
