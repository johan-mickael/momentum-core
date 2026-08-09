<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\Command\UpdateCartItemQuantityCommand;
use Application\Cart\Result\CartResult;
use Domain\Common\Security\Authorization\UserContext;

interface UpdateCartItemQuantityUseCase
{
    public function __invoke(UpdateCartItemQuantityCommand $command, UserContext $actor): CartResult;
}
