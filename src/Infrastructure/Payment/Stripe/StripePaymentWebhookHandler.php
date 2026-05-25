<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment\Stripe;

use App\Application\Order\Command\MarkOrderAsPaidCommand;
use App\Application\Order\CommandHandler\MarkOrderAsPaidUseCase;
use App\Application\Order\Exception\WebhookSignatureInvalid;
use App\Application\Order\Service\PaymentWebhookHandlerInterface;
use App\Domain\Order\Exception\CannotPayCancelledOrder;
use App\Domain\Order\Exception\OrderAlreadyPaid;
use DateTimeImmutable;
use DateTimeInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final readonly class StripePaymentWebhookHandler implements PaymentWebhookHandlerInterface
{
    public function __construct(
        private string $webhookSecret,
        private MarkOrderAsPaidUseCase $markOrderAsPaid,
    ) {}

    public function handle(string $rawPayload, array $headers): void
    {
        $signature = $this->extractSignature($headers);

        try {
            $event = Webhook::constructEvent($rawPayload, $signature, $this->webhookSecret);
        } catch (SignatureVerificationException) {
            throw WebhookSignatureInvalid::create();
        }

        if ($event->type !== 'checkout.session.completed') {
            return;
        }

        $orderId = $event->data->object->metadata->orderId ?? null;

        if ($orderId === null) {
            return;
        }

        $paidAt = new DateTimeImmutable('@' . $event->data->object->created);

        try {
            ($this->markOrderAsPaid)(new MarkOrderAsPaidCommand(
                orderId: $orderId,
                paidAt:  $paidAt->format(DateTimeInterface::ATOM),
            ));
        } catch (OrderAlreadyPaid | CannotPayCancelledOrder) {
            // Stripe delivers webhooks at-least-once — idempotent on duplicates
            // and on payment arriving after order expiry (slot already freed).
        }
    }

    /** @param array<string, string|string[]> $headers */
    private function extractSignature(array $headers): string
    {
        $value = $headers['stripe-signature'] ?? $headers['Stripe-Signature'] ?? '';

        return is_array($value) ? ($value[0] ?? '') : $value;
    }
}
