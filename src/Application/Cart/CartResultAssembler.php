<?php

declare(strict_types=1);

namespace Application\Cart;

use Application\Cart\Result\CartItemResult;
use Application\Cart\Result\CartResult;
use Domain\Cart\Cart;
use Domain\Cart\CartStatus;
use Domain\Event\Event;

/**
 * Builds the buyer-facing CartResult from the Cart aggregate plus live
 * category data (name/price/stock) on the owning Event — pricing is never
 * stored on the cart itself, so it can never go stale while shopping.
 */
final class CartResultAssembler
{
    public function assemble(Cart $cart, Event $event): CartResult
    {
        $currency = '';
        $total = 0;
        $items = [];

        foreach ($cart->items() as $item) {
            $category = $event->categoryById($item->categoryId());
            $unitPrice = $category->price();
            $subtotal = $unitPrice->amount() * $item->quantity();

            $items[] = new CartItemResult(
                categoryId: $item->categoryId()->toString(),
                categoryName: $category->name(),
                quantity: $item->quantity(),
                unitPriceAmount: $unitPrice->amount(),
                unitPriceCurrency: $unitPrice->currency()->toString(),
                subtotalAmount: $subtotal,
                availableQuantity: $category->availableQuantity(),
            );

            $total += $subtotal;
            $currency = $unitPrice->currency()->toString();
        }

        return new CartResult(
            id: $cart->id()->toString(),
            status: $cart->status()->value,
            expiresAt: CartStatus::ACTIVE === $cart->status() ? $cart->expiresAt()->format(\DateTimeInterface::ATOM) : null,
            items: $items,
            totalAmount: $total,
            currency: $currency,
        );
    }
}
