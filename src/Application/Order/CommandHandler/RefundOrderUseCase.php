<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Order\Command\RefundOrderCommand;
use Domain\Common\Security\Authorization\UserContext;

interface RefundOrderUseCase
{
    public function __invoke(RefundOrderCommand $command, UserContext $actor): void;
}
