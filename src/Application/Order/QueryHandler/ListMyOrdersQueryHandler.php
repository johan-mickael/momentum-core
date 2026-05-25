<?php

declare(strict_types=1);

namespace App\Application\Order\QueryHandler;

use App\Application\Order\Query\ListMyOrdersQuery;
use App\Application\Order\Result\OrderItemResult;
use App\Application\Order\Result\OrderResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Order\Exception\ListOrdersForbidden;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepositoryInterface;
use DateTimeInterface;

final readonly class ListMyOrdersQueryHandler implements ListMyOrdersUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    /** @return OrderResult[] */
    public function __invoke(ListMyOrdersQuery $query, UserContext $actor): array
    {
        if (!$this->authorizationService->authorize($actor, Action::LIST, Order::class)) {
            throw ListOrdersForbidden::create();
        }

        return array_map(
            fn(Order $order) => $this->toResult($order),
            $this->orders->findByBuyerId($actor->id),
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
