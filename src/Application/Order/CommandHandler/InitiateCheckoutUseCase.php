<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Order\Command\InitiateCheckoutCommand;
use Application\Order\Result\CheckoutResult;
use Domain\Common\Security\Authorization\UserContext;

interface InitiateCheckoutUseCase
{
    public function __invoke(InitiateCheckoutCommand $command, UserContext $actor): CheckoutResult;
}
