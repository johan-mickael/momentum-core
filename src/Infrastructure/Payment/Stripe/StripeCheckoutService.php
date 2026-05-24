<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment\Stripe;

use App\Application\Order\Service\CheckoutServiceInterface;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use Stripe\StripeClient;

final readonly class StripeCheckoutService implements CheckoutServiceInterface
{
    public function __construct(
        private StripeClient $stripe,
        private string $successUrl,
        private string $cancelUrl,
    ) {}

    public function createSession(Order $order): string
    {
        $session = $this->stripe->checkout->sessions->create([
            'mode'        => 'payment',
            'line_items'  => $this->buildLineItems($order),
            'success_url' => $this->successUrl,
            'cancel_url'  => $this->cancelUrl,
            'metadata'    => [
                'orderId' => $order->id()->toString(),
            ],
        ]);

        return $session->url;
    }

    private function buildLineItems(Order $order): array
    {
        $grouped = [];

        foreach ($order->items() as $item) {
            $key = $item->categoryId()->toString();
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'price_data' => [
                        'currency'     => strtolower($item->unitPrice()->currency()->toString()),
                        'unit_amount'  => $item->unitPrice()->amount(),
                        'product_data' => [
                            'name' => sprintf('Ticket — %s', $item->categoryId()->toString()),
                        ],
                    ],
                    'quantity' => 0,
                ];
            }
            $grouped[$key]['quantity']++;
        }

        return array_values($grouped);
    }
}
