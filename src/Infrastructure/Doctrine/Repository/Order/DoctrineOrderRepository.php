<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Repository\Order;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Domain\Common\ValueObject\PaginatedList;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Common\ValueObject\SortDirection;
use Domain\Event\ValueObject\EventId;
use Domain\Order\Exception\OrderNotFound;
use Domain\Order\Order;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\OrderStatus;
use Domain\Order\ValueObject\OrderId;
use Domain\User\ValueObject\UserId;
use Infrastructure\Doctrine\Entity\OrderEntity;
use Infrastructure\Doctrine\Mapper\OrderMapper;

final readonly class DoctrineOrderRepository implements OrderRepositoryInterface
{
    private const array SORT_FIELD_MAP = [
        'placedAt' => 'o.placedAt',
        'totalAmount' => 'o.totalAmount',
    ];
    private const string DEFAULT_SORT_FIELD = 'o.placedAt';

    public function __construct(
        private EntityManagerInterface $em,
        private OrderMapper $mapper,
    ) {
    }

    public function getById(OrderId $id): Order
    {
        $entity = $this->em->find(OrderEntity::class, $id->toString());

        if (null === $entity) {
            throw OrderNotFound::forId($id);
        }

        return $this->mapper->toDomain($entity);
    }

    public function getByIdForUpdate(OrderId $id): Order
    {
        $entity = $this->em->find(OrderEntity::class, $id->toString(), LockMode::PESSIMISTIC_WRITE);

        if (null === $entity) {
            throw OrderNotFound::forId($id);
        }

        return $this->mapper->toDomain($entity);
    }

    public function findPendingExpiredBefore(\DateTimeImmutable $expiresAt): array
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

        return array_map(fn (OrderEntity $e) => $this->mapper->toDomain($e), $entities);
    }

    public function findPendingByEventId(EventId $eventId): array
    {
        $entities = $this->em->createQueryBuilder()
            ->select('o')
            ->from(OrderEntity::class, 'o')
            ->where('o.status = :status')
            ->andWhere('o.eventId = :eventId')
            ->setParameter('status', OrderStatus::PENDING_PAYMENT)
            ->setParameter('eventId', (string) $eventId)
            ->getQuery()
            ->getResult();

        return array_map(fn (OrderEntity $e) => $this->mapper->toDomain($e), $entities);
    }

    public function findByBuyerId(
        UserId $buyerId,
        PaginationCriteria $pagination,
        SortCriteria $sort,
        ?OrderStatus $statusFilter = null,
    ): PaginatedList {
        $qb = $this->em->createQueryBuilder()
            ->select('o')
            ->from(OrderEntity::class, 'o')
            ->where('o.buyerId = :buyerId')
            ->setParameter('buyerId', $buyerId->__toString());

        if (null !== $statusFilter) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $statusFilter);
        }

        $sortColumn = self::SORT_FIELD_MAP[$sort->field] ?? self::DEFAULT_SORT_FIELD;
        $qb->orderBy($sortColumn, SortDirection::DESC === $sort->direction ? 'DESC' : 'ASC')
           ->setFirstResult($pagination->offset())
           ->setMaxResults($pagination->limit);

        $paginator = new Paginator($qb->getQuery());

        return new PaginatedList(
            items: array_map(fn (OrderEntity $e) => $this->mapper->toDomain($e), iterator_to_array($paginator->getIterator())),
            total: count($paginator),
            page: $pagination->page,
            limit: $pagination->limit,
        );
    }

    public function save(Order $order): void
    {
        $entity = $this->em->find(OrderEntity::class, $order->id()->toString());

        if (null === $entity) {
            $this->em->persist($this->mapper->toEntity($order));

            return;
        }

        $this->mapper->updateEntity($entity, $order);
    }
}
