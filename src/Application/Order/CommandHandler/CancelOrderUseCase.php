<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Order\Command\CancelOrderCommand;
use Domain\Common\Security\Authorization\UserContext;

interface CancelOrderUseCase
{
    public function __invoke(CancelOrderCommand $command, UserContext $actor): void;
}
