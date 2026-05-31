<?php

declare(strict_types=1);

namespace Domain\Event\Exception;

use Domain\Event\EventStatus;

final class InvalidEventStatusTransition extends \DomainException
{
    public static function cannotPublish(EventStatus $current): self
    {
        return new self(
            "Cannot publish an event with status '{$current->value}'."
        );
    }

    public static function cannotCancel(EventStatus $current): self
    {
        return new self(
            "Cannot cancel an event with status '{$current->value}'."
        );
    }

    public static function cannotEnd(EventStatus $current): self
    {
        return new self(
            "Cannot end an event with status '{$current->value}'."
        );
    }

    public static function cannotReschedule(EventStatus $current): self
    {
        return new self(
            "Cannot reschedule an event with status '{$current->value}'."
        );
    }

    public static function cannotRelocate(EventStatus $current): self
    {
        return new self(
            "Cannot relocate an event with status '{$current->value}'."
        );
    }
}
