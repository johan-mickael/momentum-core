<?php

declare(strict_types=1);

namespace Application\Common\Bus;

interface CommandBusInterface
{
    /** @param array<mixed> $stamps */
    public function dispatch(object $command, array $stamps = []): mixed;
}
