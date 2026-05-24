<?php

declare(strict_types=1);

namespace App\UI\Http\Request\Order;

use Symfony\Component\Validator\Constraints as Assert;

final class MarkOrderAsPaidRequest
{
    #[Assert\NotBlank(message: 'paidAt is required.')]
    #[Assert\DateTime(format: \DateTimeInterface::ATOM, message: 'paidAt must be a valid ISO 8601 datetime.')]
    public ?string $paidAt = null;
}
