<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class InvalidDateRange extends EventException
{
    public static function endBeforeStart(\DateTimeImmutable $start, \DateTimeImmutable $end): self
    {
        return new self(sprintf(
            'Event end date (%s) must be after start date (%s).',
            $end->format('Y-m-d H:i:s'),
            $start->format('Y-m-d H:i:s'),
        ));
    }
}
