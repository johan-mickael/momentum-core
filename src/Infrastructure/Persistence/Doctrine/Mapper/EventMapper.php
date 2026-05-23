<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Event\Event;
use App\Domain\Event\TicketCategory;
use App\Domain\Event\ValueObject\DateRange;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\EventSlug;
use App\Domain\Venue\ValueObject\VenueId;
use App\Infrastructure\Persistence\Doctrine\Entity\EventEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\TicketCategoryEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\VenueEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\Exception\CannotMapEventException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;

final readonly class EventMapper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketCategoryMapper $categoryMapper,
    ) {}

    public function toDomain(EventEntity $eventEntity): Event
    {
        $categories = array_map(
            fn(TicketCategoryEntity $e) => $this->categoryMapper->toDomain($e),
            $eventEntity->getTicketCategories()->toArray(),
        );

        return Event::reconstitute(
            id: EventId::fromString($eventEntity->getId()),
            name: $eventEntity->getName(),
            venueId: VenueId::fromString($eventEntity->getVenue()->id),
            slug: new EventSlug($eventEntity->getSlug()),
            dateRange: DateRange::reconstitute(
                $eventEntity->getStartAt(),
                $eventEntity->getEndAt(),
            ),
            status: $eventEntity->getStatus(),
            createdAt: $eventEntity->getCreatedAt(),
            ticketCategories: $categories,
            description: $eventEntity->getDescription(),
            imageUrl: $eventEntity->getImageUrl(),
        );
    }

    /**
     * @throws CannotMapEventException
     */
    public function toEntity(Event $event): EventEntity
    {
        try {
            $venueEntity = $this->entityManager->getReference(VenueEntity::class, (string) $event->venueId());
        } catch (ORMException $e) {
            throw new CannotMapEventException($event, $e->getCode(), $e);
        }

        return new EventEntity(
            (string) $event->id(),
            $venueEntity,
            $event->name(),
            (string) $event->slug(),
            $event->dateRange()->startAt,
            $event->dateRange()->endAt,
            $event->status(),
            $event->description(),
            $event->imageUrl(),
        );
    }

    public function updateEntity(EventEntity $entity, Event $event): void
    {
        $entity->setName($event->name());
        $entity->setStatus($event->status());
        $entity->setStartAt($event->dateRange()->startAt);
        $entity->setEndAt($event->dateRange()->endAt);
        $entity->setDescription($event->description());
        $entity->setImageUrl($event->imageUrl());

        $existingById = [];
        foreach ($entity->getTicketCategories() as $categoryEntity) {
            $existingById[$categoryEntity->getId()] = $categoryEntity;
        }

        foreach ($event->ticketCategories() as $category) {
            $id = $category->id()->toString();

            if (isset($existingById[$id])) {
                $existingById[$id]->setReservedQuantity($category->reservedQuantity());
            } else {
                $entity->addTicketCategory($this->categoryMapper->toEntity($category, $entity));
            }
        }
    }
}
