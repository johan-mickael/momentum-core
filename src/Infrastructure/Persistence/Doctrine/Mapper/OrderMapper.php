<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Common\ValueObject\Currency;
use App\Domain\Common\ValueObject\Money;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\TicketCategoryId;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Order\ValueObject\PaymentReference;
use App\Domain\Ticket\ValueObject\TicketId;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Doctrine\Entity\OrderEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\OrderItemEntity;

final class OrderMapper
{
    public function toDomain(OrderEntity $entity): Order
    {
        $items = array_map(
            static fn(OrderItemEntity $i) => new OrderItem(
                categoryId: TicketCategoryId::fromString($i->getCategoryId()),
                ticketId:   TicketId::fromString($i->getTicketId()),
                unitPrice:  new Money(
                    $i->getUnitPriceAmount(),
                    Currency::fromString($i->getUnitPriceCurrency()),
                ),
            ),
            $entity->getItems()->toArray(),
        );

        $rawRef = $entity->getPaymentReference();

        return Order::reconstitute(
            id:               OrderId::fromString($entity->getId()),
            buyerId:          UserId::fromString($entity->getBuyerId()),
            eventId:          EventId::fromString($entity->getEventId()),
            items:            $items,
            total:            new Money(
                $entity->getTotalAmount(),
                Currency::fromString($entity->getTotalCurrency()),
            ),
            status:           $entity->getStatus(),
            placedAt:         $entity->getPlacedAt(),
            expiresAt:        $entity->getExpiresAt(),
            paidAt:           $entity->getPaidAt(),
            cancelledAt:      $entity->getCancelledAt(),
            paymentReference: $rawRef !== null ? PaymentReference::fromString($rawRef) : null,
        );
    }

    public function toEntity(Order $order): OrderEntity
    {
        $entity = new OrderEntity(
            id:               $order->id()->toString(),
            buyerId:          (string) $order->buyerId(),
            eventId:          (string) $order->eventId(),
            status:           $order->status(),
            totalAmount:      $order->total()->amount(),
            totalCurrency:    $order->total()->currency()->toString(),
            placedAt:         $order->placedAt(),
            expiresAt:        $order->expiresAt(),
            paidAt:           $order->paidAt(),
            cancelledAt:      $order->cancelledAt(),
            paymentReference: $order->paymentReference()?->toString(),
        );

        foreach ($order->items() as $item) {
            $entity->addItem(new OrderItemEntity(
                order:             $entity,
                categoryId:        $item->categoryId()->toString(),
                ticketId:          $item->ticketId()->toString(),
                unitPriceAmount:   $item->unitPrice()->amount(),
                unitPriceCurrency: $item->unitPrice()->currency()->toString(),
            ));
        }

        return $entity;
    }

    public function updateEntity(OrderEntity $entity, Order $order): void
    {
        $entity->setStatus($order->status());
        $entity->setPaidAt($order->paidAt());
        $entity->setCancelledAt($order->cancelledAt());
        $entity->setPaymentReference($order->paymentReference()?->toString());
    }
}
