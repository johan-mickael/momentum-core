<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Order\Command\InitiateCheckoutCommand;
use App\Application\Order\Result\CheckoutResult;
use App\Domain\Common\Security\Authorization\UserContext;

interface InitiateCheckoutUseCase
{
    public function __invoke(InitiateCheckoutCommand $command, UserContext $actor): CheckoutResult;
}
