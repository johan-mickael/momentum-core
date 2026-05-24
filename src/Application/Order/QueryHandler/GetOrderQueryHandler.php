<?php

declare(strict_types=1);

namespace App\Application\Order\QueryHandler;

use App\Application\Order\Query\GetOrderQuery;
use App\Application\Order\Result\OrderItemResult;
use App\Application\Order\Result\OrderResult;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Order\Exception\ViewOrderForbidden;
use App\Domain\Order\Order;
use App\Domain\Order\OrderItem;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\Security\Authorization\OrderContext;
use App\Domain\Order\ValueObject\OrderId;
use DateTimeInterface;

final readonly class GetOrderQueryHandler implements GetOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

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
