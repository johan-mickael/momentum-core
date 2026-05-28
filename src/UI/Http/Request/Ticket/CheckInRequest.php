<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Ticket;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CheckInRequest',
    required: ['entryCode'],
    properties: [
        new OA\Property(property: 'entryCode', type: 'string', format: 'uuid', description: 'Ticket entry code (from QR scan)'),
    ],
)]
final class CheckInRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $entryCode,
    ) {}
}
