<?php

declare(strict_types=1);

namespace App\Application\Order\Service;

use DateTimeImmutable;

final readonly class WebhookEvent
{
    public function __construct(
        public string           $type,
        public ?string          $orderId,
        public DateTimeImmutable $occurredAt,
    ) {}
}
