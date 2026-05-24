<?php

declare(strict_types=1);

namespace App\Application\Order\Exception;

use RuntimeException;

final class WebhookSignatureInvalid extends RuntimeException
{
    public static function create(): self
    {
        return new self('Invalid webhook signature.');
    }
}
