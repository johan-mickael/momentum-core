<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\Command\CheckoutCartCommand;
use Application\Order\Result\PlaceOrderResult;
use Domain\Common\Security\Authorization\UserContext;

interface CheckoutCartUseCase
{
    public function __invoke(CheckoutCartCommand $command, UserContext $actor): PlaceOrderResult;
}
