<?php

declare(strict_types=1);

namespace App\Application\Order\QueryHandler;

use App\Application\Common\Result\PaginatedResult;
use App\Application\Order\Query\ListMyOrdersQuery;
use App\Application\Order\Result\OrderItemResult;
use App\Application\Order\Result\OrderResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\ValueObject\PaginationCriteria;
use App\Domain\Common\ValueObject\SortCriteria;
use App\Domain\Common\ValueObject\SortDirection;
use App\Domain\Order\Exception\ListOrdersForbidden;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\OrderStatus;
use DateTimeInterface;

final readonly class ListMyOrdersQueryHandler implements ListMyOrdersUseCase
{
    public function __construct(
        private OrderRepositoryInterface      $orders,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function __invoke(ListMyOrdersQuery $query, UserContext $actor): PaginatedResult
    {
        if (!$this->authorizationService->authorize($actor, Action::LIST, Order::class)) {
            throw ListOrdersForbidden::create();
        }

        $list = $this->orders->findByBuyerId(
            $actor->id,
            new PaginationCriteria($query->page, $query->limit),
            new SortCriteria($query->sort, SortDirection::fromString($query->direction)),
            $query->statusFilter !== null ? OrderStatus::tryFrom($query->statusFilter) : null,
        );

        return new PaginatedResult(
            data:  array_map(fn(Order $order) => $this->toResult($order), $list->items),
            total: $list->total,
            page:  $list->page,
            limit: $list->limit,
        );
    }

    private function toResult(Order $order): OrderResult
    {
        return new OrderResult(
            id:          $order->id()->toString(),
            status:      $order->status()->value,
            buyerId:     (string) $order->buyerId(),
            eventId:     (string) $order->eventId(),
            totalAmount: $order->total()->amount(),
            currency:    $order->total()->currency()->toString(),
            placedAt:    $order->placedAt()->format(DateTimeInterface::ATOM),
            expiresAt:   $order->expiresAt()->format(DateTimeInterface::ATOM),
            paidAt:      $order->paidAt()?->format(DateTimeInterface::ATOM),
            cancelledAt: $order->cancelledAt()?->format(DateTimeInterface::ATOM),
            items:       array_map(
                static fn(OrderItem $item) => new OrderItemResult(
                    categoryId:        $item->categoryId()->toString(),
                    ticketId:          $item->ticketId()->toString(),
                    unitPriceAmount:   $item->unitPrice()->amount(),
                    unitPriceCurrency: $item->unitPrice()->currency()->toString(),
                ),
                $order->items(),
            ),
        );
    }
}
