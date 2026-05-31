<?php

declare(strict_types=1);

namespace Domain\Event;

enum EventStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case CANCELLED = 'CANCELLED';
    case ENDED = 'ENDED';
}
