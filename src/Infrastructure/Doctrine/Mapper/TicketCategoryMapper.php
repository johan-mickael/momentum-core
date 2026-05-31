<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Mapper;

use Domain\Common\ValueObject\Currency;
use Domain\Common\ValueObject\Money;
use Domain\Event\TicketCategory;
use Domain\Event\ValueObject\TicketCategoryId;
use Infrastructure\Doctrine\Entity\EventEntity;
use Infrastructure\Doctrine\Entity\TicketCategoryEntity;

final class TicketCategoryMapper
{
    public function toDomain(TicketCategoryEntity $entity): TicketCategory
    {
        return TicketCategory::reconstitute(
            id: TicketCategoryId::fromString($entity->getId()),
            name: $entity->getName(),
            price: new Money($entity->getPriceAmount(), Currency::fromString($entity->getPriceCurrency())),
            totalQuantity: $entity->getTotalQuantity(),
            reservedQuantity: $entity->getReservedQuantity(),
        );
    }

    public function toEntity(TicketCategory $category, EventEntity $eventEntity): TicketCategoryEntity
    {
        return new TicketCategoryEntity(
            id: $category->id()->toString(),
            event: $eventEntity,
            name: $category->name(),
            priceAmount: $category->price()->amount(),
            priceCurrency: $category->price()->currency()->toString(),
            totalQuantity: $category->totalQuantity(),
            reservedQuantity: $category->reservedQuantity(),
        );
    }
}
