<?php

declare(strict_types=1);

namespace Domain\Event\Security\Authorization;

use Domain\Common\Security\Authorization\Subject;
use Domain\Event\Event;

final readonly class EventContext implements Subject
{
    public function __construct(
        public Event $event,
    ) {
    }
}
