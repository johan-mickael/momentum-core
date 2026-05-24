<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Order\Command\PlaceOrderCommand;
use App\Application\Order\Result\PlaceOrderResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface PlaceOrderUseCase
{
    public function __invoke(PlaceOrderCommand $command, UserContext $actor): PlaceOrderResult;
}
