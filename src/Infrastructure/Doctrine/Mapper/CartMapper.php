<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Mapper;

use Domain\Cart\Cart;
use Domain\Cart\CartItem;
use Domain\Cart\ValueObject\CartId;
use Domain\Event\ValueObject\EventId;
use Domain\Event\ValueObject\TicketCategoryId;
use Domain\User\ValueObject\UserId;
use Infrastructure\Doctrine\Entity\CartEntity;
use Infrastructure\Doctrine\Entity\CartItemEntity;

final class CartMapper
{
    public function toDomain(CartEntity $entity): Cart
    {
        $items = array_map(
            static fn (CartItemEntity $i) => new CartItem(
                categoryId: TicketCategoryId::fromString($i->getCategoryId()),
                quantity: $i->getQuantity(),
                addedAt: $i->getAddedAt(),
            ),
            $entity->getItems()->toArray(),
        );

        return Cart::reconstitute(
            id: CartId::fromString($entity->getId()),
            buyerId: UserId::fromString($entity->getBuyerId()),
            eventId: EventId::fromString($entity->getEventId()),
            status: $entity->getStatus(),
            createdAt: $entity->getCreatedAt(),
            expiresAt: $entity->getExpiresAt(),
            items: $items,
        );
    }

    public function toEntity(Cart $cart): CartEntity
    {
        $entity = new CartEntity(
            id: $cart->id()->toString(),
            buyerId: (string) $cart->buyerId(),
            eventId: (string) $cart->eventId(),
            status: $cart->status(),
            createdAt: $cart->createdAt(),
            expiresAt: $cart->expiresAt(),
        );

        foreach ($cart->items() as $item) {
            $entity->addItem(new CartItemEntity(
                cart: $entity,
                categoryId: $item->categoryId()->toString(),
                quantity: $item->quantity(),
                addedAt: $item->addedAt(),
            ));
        }

        return $entity;
    }

    /**
     * Cart items are mutable (added/removed/re-quantified), unlike Order's
     * append-only items, so the collection must be diffed rather than only
     * ever appended to.
     */
    public function updateEntity(CartEntity $entity, Cart $cart): void
    {
        $entity->setStatus($cart->status());
        $entity->setExpiresAt($cart->expiresAt());

        $domainItemsByCategory = [];
        foreach ($cart->items() as $item) {
            $domainItemsByCategory[$item->categoryId()->toString()] = $item;
        }

        foreach ($entity->getItems()->toArray() as $itemEntity) {
            $categoryId = $itemEntity->getCategoryId();

            if (!isset($domainItemsByCategory[$categoryId])) {
                $entity->removeItem($itemEntity);
                continue;
            }

            $itemEntity->setQuantity($domainItemsByCategory[$categoryId]->quantity());
            unset($domainItemsByCategory[$categoryId]);
        }

        /** @var CartItem $item */
        foreach ($domainItemsByCategory as $item) {
            $entity->addItem(new CartItemEntity(
                cart: $entity,
                categoryId: $item->categoryId()->toString(),
                quantity: $item->quantity(),
                addedAt: $item->addedAt(),
            ));
        }
    }
}
