<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\Command\RemoveCartItemCommand;
use Application\Cart\Result\CartResult;
use Domain\Common\Security\Authorization\UserContext;

interface RemoveCartItemUseCase
{
    public function __invoke(RemoveCartItemCommand $command, UserContext $actor): CartResult;
}
