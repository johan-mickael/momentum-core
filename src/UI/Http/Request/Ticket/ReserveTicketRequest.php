<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Ticket;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'ReserveTicketRequest',
    required: ['categoryId'],
    properties: [
        new OA\Property(property: 'categoryId', type: 'string', format: 'uuid'),
    ],
)]
final class ReserveTicketRequest
{
    #[Assert\NotBlank(message: 'categoryId is required.')]
    #[Assert\Uuid(message: 'categoryId must be a valid UUID.')]
    public ?string $categoryId = null;
}
