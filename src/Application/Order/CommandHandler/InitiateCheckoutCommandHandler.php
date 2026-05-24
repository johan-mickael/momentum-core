<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Order\Command\InitiateCheckoutCommand;
use App\Application\Order\Result\CheckoutResult;
use App\Application\Order\Service\CheckoutServiceInterface;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Order\Exception\CheckoutForbidden;
use App\Domain\Order\Exception\OrderNotPendingPayment;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\OrderStatus;
use App\Domain\Order\Security\Authorization\OrderContext;
use App\Domain\Order\ValueObject\OrderId;

final readonly class InitiateCheckoutCommandHandler implements InitiateCheckoutUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private CheckoutServiceInterface $checkoutService,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    public function __invoke(InitiateCheckoutCommand $command, UserContext $actor): CheckoutResult
    {
        $order = $this->orders->getById(OrderId::fromString($command->orderId));

        if (!$this->authorizationService->authorize($actor, Action::CHECKOUT, new OrderContext($order))) {
            throw CheckoutForbidden::create();
        }

        if ($order->status() !== OrderStatus::PENDING_PAYMENT) {
            throw OrderNotPendingPayment::forOrder($order->id());
        }

        $checkoutUrl = $this->checkoutService->createSession($order);

        return new CheckoutResult($checkoutUrl);
    }
}
