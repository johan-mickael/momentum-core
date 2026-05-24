<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Order;

use App\Domain\Order\Order;
use App\Domain\Order\Exception\OrderNotFound;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\OrderStatus;
use App\Domain\Order\ValueObject\OrderId;
use App\Infrastructure\Persistence\Doctrine\Entity\OrderEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\OrderMapper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderMapper            $mapper,
    ) {}

    public function getById(OrderId $id): Order
    {
        $entity = $this->em->find(OrderEntity::class, $id->toString());

        if ($entity === null) {
            throw OrderNotFound::forId($id);
        }

        return $this->mapper->toDomain($entity);
    }

    public function findPendingExpiredBefore(DateTimeImmutable $expiresAt): array
    {
        $entities = $this->em->createQueryBuilder()
            ->select('o')
            ->from(OrderEntity::class, 'o')
            ->where('o.status = :status')
            ->andWhere('o.expiresAt <= :expiresAt')
            ->setParameter('status', OrderStatus::PENDING_PAYMENT)
            ->setParameter('expiresAt', $expiresAt)
            ->getQuery()
            ->getResult();

        return array_map(fn(OrderEntity $e) => $this->mapper->toDomain($e), $entities);
    }

    public function save(Order $order): void
    {
        $entity = $this->em->find(OrderEntity::class, $order->id()->toString());

        if ($entity === null) {
            $this->em->persist($this->mapper->toEntity($order));
            return;
        }

        $this->mapper->updateEntity($entity, $order);
    }
}
