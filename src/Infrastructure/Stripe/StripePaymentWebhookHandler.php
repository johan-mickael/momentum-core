<?php

declare(strict_types=1);

namespace Infrastructure\Stripe;

use Application\Order\Command\MarkOrderAsPaidCommand;
use Application\Order\CommandHandler\MarkOrderAsPaidUseCase;
use Application\Order\Exception\WebhookSignatureInvalid;
use Application\Order\Service\PaymentWebhookHandlerInterface;
use Application\Order\Service\RefundPaymentServiceInterface;
use Domain\Order\Exception\CannotPayCancelledOrder;
use Domain\Order\Exception\OrderAlreadyPaid;
use Domain\Order\ValueObject\PaymentReference;
use Psr\Clock\ClockInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final readonly class StripePaymentWebhookHandler implements PaymentWebhookHandlerInterface
{
    public function __construct(
        private string $webhookSecret,
        private MarkOrderAsPaidUseCase $markOrderAsPaid,
        private RefundPaymentServiceInterface $refundService,
        private ClockInterface $clock,
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

        match ($event->type) {
            'checkout.session.completed' => $this->handleSessionCompleted($event),
            'checkout.session.async_payment_succeeded' => $this->handleAsyncPaymentSucceeded($event),
            default => null,
        };
    }

    private function handleSessionCompleted(\Stripe\Event $event): void
    {
        $session = $event->data->object;

        if (!$session instanceof \Stripe\Checkout\Session) {
            return;
        }

        // async payment methods (SEPA, Klarna, etc.) fire this event before money arrives
        if ('paid' !== $session->payment_status) {
            return;
        }

        $this->markOrderPaidFromSession($session);
    }

    private function handleAsyncPaymentSucceeded(\Stripe\Event $event): void
    {
        $session = $event->data->object;

        if (!$session instanceof \Stripe\Checkout\Session) {
            return;
        }

        $this->markOrderPaidFromSession($session);
    }

    private function markOrderPaidFromSession(\Stripe\Checkout\Session $session): void
    {
        $orderId = $session->metadata->orderId ?? null;

        if (null === $orderId) {
            return;
        }

        $paymentIntent = $session->payment_intent ?? null;
        $paidAt = $this->clock->now()->format(\DateTimeInterface::ATOM);

        try {
            ($this->markOrderAsPaid)(new MarkOrderAsPaidCommand(
                orderId: $orderId,
                paidAt: $paidAt,
                paymentReference: is_string($paymentIntent) ? $paymentIntent : null,
            ));
        } catch (OrderAlreadyPaid) {
            // Stripe delivers webhooks at-least-once — idempotent duplicate
        } catch (CannotPayCancelledOrder) {
            // order expired before payment arrived — refund immediately so buyer is not charged
            if (is_string($paymentIntent)) {
                $this->refundService->refund(PaymentReference::fromString($paymentIntent));
            }
        }
    }

    /** @param array<string, string|string[]> $headers */
    private function extractSignature(array $headers): string
    {
        $value = $headers['stripe-signature'] ?? $headers['Stripe-Signature'] ?? '';

        return is_array($value) ? ($value[0] ?? '') : $value;
    }
}
