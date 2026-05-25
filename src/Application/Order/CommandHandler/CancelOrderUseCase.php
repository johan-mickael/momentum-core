<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Order\Command\CancelOrderCommand;
use App\Domain\Common\Security\Authorization\UserContext;

interface CancelOrderUseCase
{
    public function __invoke(CancelOrderCommand $command, UserContext $actor): void;
}
