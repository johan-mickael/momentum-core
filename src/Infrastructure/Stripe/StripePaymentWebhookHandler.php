<?php

declare(strict_types=1);

namespace Infrastructure\Stripe;

use Application\Order\Command\MarkOrderAsPaidCommand;
use Application\Order\CommandHandler\MarkOrderAsPaidUseCase;
use Application\Order\Exception\WebhookSignatureInvalid;
use Application\Order\Service\PaymentWebhookHandlerInterface;
use Domain\Order\Exception\CannotPayCancelledOrder;
use Domain\Order\Exception\OrderAlreadyPaid;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final readonly class StripePaymentWebhookHandler implements PaymentWebhookHandlerInterface
{
    public function __construct(
        private string $webhookSecret,
        private MarkOrderAsPaidUseCase $markOrderAsPaid,
    ) {
    }

    public function handle(string $rawPayload, array $headers): void
    {
        $signature = $this->extractSignature($headers);

        try {
            $event = Webhook::constructEvent($rawPayload, $signature, $this->webhookSecret);
        } catch (SignatureVerificationException) {
            throw WebhookSignatureInvalid::create();
        }

        if ('checkout.session.completed' !== $event->type) {
            return;
        }

        $session = $event->data->object;

        if (!$session instanceof \Stripe\Checkout\Session) {
            return;
        }

        $orderId = $session->metadata->orderId ?? null;

        if (null === $orderId) {
            return;
        }

        $paidAt = new \DateTimeImmutable('@'.(string) $session->created);
        $paymentIntent = $session->payment_intent ?? null;

        try {
            ($this->markOrderAsPaid)(new MarkOrderAsPaidCommand(
                orderId: $orderId,
                paidAt: $paidAt->format(\DateTimeInterface::ATOM),
                paymentReference: is_string($paymentIntent) ? $paymentIntent : null,
            ));
        } catch (OrderAlreadyPaid|CannotPayCancelledOrder) {
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
