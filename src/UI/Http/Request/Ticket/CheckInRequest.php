<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Ticket;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckInRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $entryCode,
    ) {}
}
