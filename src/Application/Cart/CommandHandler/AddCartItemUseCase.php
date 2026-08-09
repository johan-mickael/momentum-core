<?php

declare(strict_types=1);

namespace Application\Cart\CommandHandler;

use Application\Cart\Command\AddCartItemCommand;
use Application\Cart\Result\CartResult;
use Domain\Common\Security\Authorization\UserContext;

interface AddCartItemUseCase
{
    public function __invoke(AddCartItemCommand $command, UserContext $actor): CartResult;
}
