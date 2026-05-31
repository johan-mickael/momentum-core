<?php

declare(strict_types=1);

namespace Application\Order\QueryHandler;

use Application\Order\Query\GetOrderQuery;
use Application\Order\Result\OrderItemResult;
use Application\Order\Result\OrderResult;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Order\Exception\ViewOrderForbidden;
use Domain\Order\Order;
use Domain\Order\OrderItem;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\Security\Authorization\OrderContext;
use Domain\Order\ValueObject\OrderId;

final readonly class GetOrderQueryHandler implements GetOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(GetOrderQuery $query, UserContext $actor): OrderResult
    {
        $order = $this->orders->getById(OrderId::fromString($query->orderId));

        if (!$this->authorizationService->authorize($actor, Action::VIEW, new OrderContext($order))) {
            throw ViewOrderForbidden::forOrder($order->id());
        }

        return $this->toResult($order);
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
