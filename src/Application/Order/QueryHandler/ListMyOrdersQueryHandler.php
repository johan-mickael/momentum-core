<?php

declare(strict_types=1);

namespace Application\Order\QueryHandler;

use Application\Common\Result\PaginatedResult;
use Application\Order\Query\ListMyOrdersQuery;
use Application\Order\Result\OrderItemResult;
use Application\Order\Result\OrderResult;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Common\ValueObject\PaginationCriteria;
use Domain\Common\ValueObject\SortCriteria;
use Domain\Common\ValueObject\SortDirection;
use Domain\Order\Exception\ListOrdersForbidden;
use Domain\Order\Order;
use Domain\Order\OrderItem;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\OrderStatus;

final readonly class ListMyOrdersQueryHandler implements ListMyOrdersUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(ListMyOrdersQuery $query, UserContext $actor): PaginatedResult
    {
        if (!$this->authorizationService->authorize($actor, Action::LIST, Order::class)) {
            throw ListOrdersForbidden::create();
        }

        $list = $this->orders->findByBuyerId(
            $actor->id,
            new PaginationCriteria($query->page, $query->limit),
            new SortCriteria($query->sort, SortDirection::fromString($query->direction)),
            null !== $query->statusFilter ? OrderStatus::tryFrom($query->statusFilter) : null,
        );

        return new PaginatedResult(
            data: array_map(fn (Order $order) => $this->toResult($order), $list->items),
            total: $list->total,
            page: $list->page,
            limit: $list->limit,
        );
    }

    private function toResult(Order $order): OrderResult
    {
        return new OrderResult(
            id: $order->id()->toString(),
            status: $order->status()->value,
            buyerId: (string) $order->buyerId(),
            eventId: (string) $order->eventId(),
            totalAmount: $order->total()->amount(),
            currency: $order->total()->currency()->toString(),
            placedAt: $order->placedAt()->format(\DateTimeInterface::ATOM),
            expiresAt: $order->expiresAt()->format(\DateTimeInterface::ATOM),
            paidAt: $order->paidAt()?->format(\DateTimeInterface::ATOM),
            cancelledAt: $order->cancelledAt()?->format(\DateTimeInterface::ATOM),
            items: array_map(
                static fn (OrderItem $item) => new OrderItemResult(
                    categoryId: $item->categoryId()->toString(),
                    ticketId: $item->ticketId()->toString(),
                    unitPriceAmount: $item->unitPrice()->amount(),
                    unitPriceCurrency: $item->unitPrice()->currency()->toString(),
                ),
                $order->items(),
            ),
        );
    }
}
