<?php

declare(strict_types=1);

namespace Infrastructure\Stripe;

use Application\Order\Service\CheckoutServiceInterface;
use Application\Order\Service\CheckoutSessionData;
use Domain\Order\Order;
use Stripe\StripeClient;

final readonly class StripeCheckoutService implements CheckoutServiceInterface
{
    public function __construct(
        private StripeClient $stripe,
        private string $successUrl,
        private string $cancelUrl,
    ) {
    }

    public function createSession(Order $order, ?string $buyerEmail = null): CheckoutSessionData
    {
        $params = [
            'mode' => 'payment',
            'line_items' => $this->buildLineItems($order),
            'success_url' => $this->successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->cancelUrl.'?order_id='.$order->id()->toString(),
            'metadata' => [
                'orderId' => $order->id()->toString(),
            ],
        ];

        if (null !== $buyerEmail) {
            $params['customer_email'] = $buyerEmail;
        }

        $session = $this->stripe->checkout->sessions->create($params);

        return new CheckoutSessionData(url: $session->url, sessionId: $session->id);
    }

    /** @return array<int, array<string, mixed>> */
    private function buildLineItems(Order $order): array
    {
        $grouped = [];

        foreach ($order->items() as $item) {
            $key = $item->categoryId()->toString();
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'price_data' => [
                        'currency' => strtolower($item->unitPrice()->currency()->toString()),
                        'unit_amount' => $item->unitPrice()->amount(),
                        'product_data' => [
                            'name' => sprintf('Ticket — %s', $item->categoryId()->toString()),
                        ],
                    ],
                    'quantity' => 0,
                ];
            }
            ++$grouped[$key]['quantity'];
        }

        return array_values($grouped);
    }
}
