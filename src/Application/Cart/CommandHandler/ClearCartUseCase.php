<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\Command\ClearCartCommand;
use Domain\Common\Security\Authorization\UserContext;

interface ClearCartUseCase
{
    public function __invoke(ClearCartCommand $command, UserContext $actor): void;
}
