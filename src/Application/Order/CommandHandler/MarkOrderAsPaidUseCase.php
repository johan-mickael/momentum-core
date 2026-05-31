<?php

declare(strict_types=1);

namespace Application\Order\CommandHandler;

use Application\Order\Command\MarkOrderAsPaidCommand;

interface MarkOrderAsPaidUseCase
{
    public function __invoke(MarkOrderAsPaidCommand $command): void;
}
