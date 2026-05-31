<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Mapper\Exception;

use Domain\Event\Event;

class CannotMapEventException extends \RuntimeException
{
    public function __construct(Event $event, int $code, \Throwable $previous)
    {
        parent::__construct(
            sprintf(
                'Cannot map event "%s"',
                $event->id(),
            ),
            $code,
            $previous,
        );
    }
}
