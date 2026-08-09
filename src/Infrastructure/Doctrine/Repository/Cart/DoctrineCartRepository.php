<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Repository\Cart;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Domain\Cart\Cart;
use Domain\Cart\CartRepositoryInterface;
use Domain\Cart\CartStatus;
use Domain\Cart\Exception\CartNotFound;
use Domain\Cart\ValueObject\CartId;
use Domain\Event\ValueObject\EventId;
use Domain\User\ValueObject\UserId;
use Infrastructure\Doctrine\Entity\CartEntity;
use Infrastructure\Doctrine\Mapper\CartMapper;

final readonly class DoctrineCartRepository implements CartRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private CartMapper $mapper,
    ) {
    }

    public function findById(CartId $id): Cart
    {
        $entity = $this->em->find(CartEntity::class, $id->toString());

        if (null === $entity) {
            throw CartNotFound::forId($id);
        }

        return $this->mapper->toDomain($entity);
    }

    public function findByIdForUpdate(CartId $id): Cart
    {
        $entity = $this->em->find(CartEntity::class, $id->toString(), LockMode::PESSIMISTIC_WRITE);

        if (null === $entity) {
            throw CartNotFound::forId($id);
        }

        return $this->mapper->toDomain($entity);
    }

    public function findActiveByBuyerAndEvent(UserId $buyerId, EventId $eventId): ?Cart
    {
        $entity = $this->activeByBuyerAndEventQuery($buyerId, $eventId)->getOneOrNullResult();

        return null !== $entity ? $this->mapper->toDomain($entity) : null;
    }

    public function findActiveByBuyerAndEventForUpdate(UserId $buyerId, EventId $eventId): ?Cart
    {
        $entity = $this->activeByBuyerAndEventQuery($buyerId, $eventId)
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return null !== $entity ? $this->mapper->toDomain($entity) : null;
    }

    public function findActiveByEvent(EventId $eventId): array
    {
        $entities = $this->em->createQueryBuilder()
            ->select('c')
            ->from(CartEntity::class, 'c')
            ->where('c.status = :status')
            ->andWhere('c.eventId = :eventId')
            ->setParameter('status', CartStatus::ACTIVE)
            ->setParameter('eventId', (string) $eventId)
            ->getQuery()
            ->getResult();

        return array_map(fn (CartEntity $e) => $this->mapper->toDomain($e), $entities);
    }

    public function findAllActiveByBuyer(UserId $buyerId): array
    {
        $entities = $this->em->createQueryBuilder()
            ->select('c')
            ->from(CartEntity::class, 'c')
            ->where('c.status = :status')
            ->andWhere('c.buyerId = :buyerId')
            ->setParameter('status', CartStatus::ACTIVE)
            ->setParameter('buyerId', $buyerId->__toString())
            ->getQuery()
            ->getResult();

        return array_map(fn (CartEntity $e) => $this->mapper->toDomain($e), $entities);
    }

    public function findExpired(\DateTimeImmutable $before, int $limit = 100): array
    {
        $entities = $this->em->createQueryBuilder()
            ->select('c')
            ->from(CartEntity::class, 'c')
            ->where('c.status = :status')
            ->andWhere('c.expiresAt <= :before')
            ->setParameter('status', CartStatus::ACTIVE)
            ->setParameter('before', $before)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn (CartEntity $e) => $this->mapper->toDomain($e), $entities);
    }

    public function save(Cart $cart): void
    {
        $entity = $this->em->find(CartEntity::class, $cart->id()->toString());

        if (null === $entity) {
            $this->em->persist($this->mapper->toEntity($cart));

            return;
        }

        $this->mapper->updateEntity($entity, $cart);
    }

    private function activeByBuyerAndEventQuery(UserId $buyerId, EventId $eventId): Query
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(CartEntity::class, 'c')
            ->where('c.status = :status')
            ->andWhere('c.buyerId = :buyerId')
            ->andWhere('c.eventId = :eventId')
            ->setParameter('status', CartStatus::ACTIVE)
            ->setParameter('buyerId', $buyerId->__toString())
            ->setParameter('eventId', (string) $eventId)
            ->getQuery();
    }
}
