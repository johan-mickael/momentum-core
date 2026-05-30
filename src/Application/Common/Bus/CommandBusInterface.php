<?php

declare(strict_types=1);

namespace App\Application\Common\Bus;

interface CommandBusInterface
{
    public function dispatch(object $command, array $stamps = []): mixed;
}
