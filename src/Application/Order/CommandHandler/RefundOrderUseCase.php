<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Order\Command\RefundOrderCommand;
use App\Domain\Common\Security\Authorization\UserContext;

interface RefundOrderUseCase
{
    public function __invoke(RefundOrderCommand $command, UserContext $actor): void;
}
