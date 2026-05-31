<?php

declare(strict_types=1);

namespace Application\Order\Service;

use Application\Order\Exception\WebhookSignatureInvalid;

interface PaymentWebhookHandlerInterface
{
    /** @param array<string, string|string[]> $headers
     *  @throws WebhookSignatureInvalid
     */
    public function handle(string $rawPayload, array $headers): void;
}
