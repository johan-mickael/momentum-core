<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Order\Command\PlaceOrderCommand;
use Application\Order\Result\PlaceOrderResult;
use Domain\Common\Security\Authorization\UserContext;

interface PlaceOrderUseCase
{
    public function __invoke(PlaceOrderCommand $command, UserContext $actor): PlaceOrderResult;
}
