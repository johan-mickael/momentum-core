<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment\Stripe;

use App\Application\Order\Exception\WebhookSignatureInvalid;
use App\Application\Order\Service\WebhookEvent;
use App\Application\Order\Service\WebhookParserInterface;
use DateTimeImmutable;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;

final readonly class StripeWebhookParser implements WebhookParserInterface
{
    public function __construct(
        private StripeClient $stripe,
        private string $webhookSecret,
    ) {}

    public function parse(string $payload, string $signature): WebhookEvent
    {
        try {
            $event = $this->stripe->webhooks->constructEvent($payload, $signature, $this->webhookSecret);
        } catch (SignatureVerificationException) {
            throw WebhookSignatureInvalid::create();
        }

        $orderId = null;

        if ($event->type === 'checkout.session.completed') {
            $orderId = $event->data->object->metadata->orderId ?? null;
        }

        return new WebhookEvent(
            type:       $event->type,
            orderId:    $orderId,
            occurredAt: new DateTimeImmutable('@' . $event->data->object->created),
        );
    }
}
