<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository\Order;

use App\Domain\Common\ValueObject\PaginatedList;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\Domain\Event\ValueObject\EventId;
use App\Domain\Order\Exception\OrderNotFound;
use App\Domain\Order\Order;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\OrderStatus;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Doctrine\Entity\OrderEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\OrderMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

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
