<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class ListEventsForbidden extends EventException
{
    public static function create(): self
    {
        return new self('You are not allowed to list events.');
    }
}
