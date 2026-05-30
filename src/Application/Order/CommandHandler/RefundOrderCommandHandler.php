<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Common\Event\DomainEventDispatcherInterface;
use App\Application\Order\Command\RefundOrderCommand;
use App\Application\Order\Exception\OrderHasNoPaymentReference;
use App\Application\Order\Service\RefundPaymentServiceInterface;
use App\Domain\Common\Security\Authorization\Action;
use App\Domain\Common\Security\Authorization\AuthorizationServiceInterface;
use App\Domain\Common\Security\Authorization\UserContext;
use App\Domain\Common\Transaction\TransactionManagerInterface;
use App\Domain\Order\Exception\CancelOrderForbidden;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\Security\Authorization\OrderContext;
use App\Domain\Order\ValueObject\OrderId;
use Psr\Clock\ClockInterface;

final readonly class RefundOrderCommandHandler implements RefundOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private RefundPaymentServiceInterface $refundService,
        private AuthorizationServiceInterface $authorizationService,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RefundOrderCommand $command, UserContext $actor): void
    {
        $order = $this->orders->getById(OrderId::fromString($command->orderId));

        if (!$this->authorizationService->authorize($actor, Action::CANCEL, new OrderContext($order))) {
            throw CancelOrderForbidden::create();
        }

        $paymentReference = $order->paymentReference();

        if (null === $paymentReference) {
            throw OrderHasNoPaymentReference::forOrder($order->id());
        }

        $this->refundService->refund($paymentReference);

        $order->refund($this->clock->now());
        $this->orders->save($order);
        $this->dispatcher->dispatch(...$order->pullRecordedEvents());
        $this->transactionManager->flush();
    }
}
