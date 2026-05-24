<?php

declare(strict_types=1);

namespace App\Application\Order\CommandHandler;

use App\Application\Order\Command\MarkOrderAsPaidCommand;
use App\Domain\Common\Security\Authorization\UserContext;

interface MarkOrderAsPaidUseCase
{
    public function __invoke(MarkOrderAsPaidCommand $command, UserContext $actor): void;
}
