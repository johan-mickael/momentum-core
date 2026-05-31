<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Order\Command\InitiateCheckoutCommand;
use Application\Order\Result\CheckoutResult;
use Application\Order\Service\CheckoutServiceInterface;
use Domain\Common\Security\Authorization\Action;
use Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use Domain\Common\Security\Authorization\UserContext;
use Domain\Order\Exception\CheckoutForbidden;
use Domain\Order\Exception\OrderNotPendingPayment;
use Domain\Order\OrderRepositoryInterface;
use Domain\Order\OrderStatus;
use Domain\Order\Security\Authorization\OrderContext;
use Domain\Order\ValueObject\OrderId;

final readonly class InitiateCheckoutCommandHandler implements InitiateCheckoutUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private CheckoutServiceInterface $checkoutService,
        private AuthorizationServiceInterface $authorizationService,
    ) {
    }

    public function __invoke(InitiateCheckoutCommand $command, UserContext $actor): CheckoutResult
    {
        $order = $this->orders->getById(OrderId::fromString($command->orderId));

        if (!$this->authorizationService->authorize($actor, Action::CHECKOUT, new OrderContext($order))) {
            throw CheckoutForbidden::create();
        }

        if (OrderStatus::PENDING_PAYMENT !== $order->status()) {
            throw OrderNotPendingPayment::forOrder($order->id());
        }

        $checkoutUrl = $this->checkoutService->createSession($order);

        return new CheckoutResult($checkoutUrl);
    }
}
