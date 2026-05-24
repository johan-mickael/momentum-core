<?php

declare(strict_types=1);

namespace App\Application\Order\Service;

use App\Application\Order\Exception\WebhookSignatureInvalid;

interface WebhookParserInterface
{
    /** @throws WebhookSignatureInvalid */
    public function parse(string $payload, string $signature): WebhookEvent;
}
