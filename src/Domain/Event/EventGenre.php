<?php

declare(strict_types=1);

namespace Domain\Event;

enum EventGenre: string
{
    case MUSIC = 'MUSIC';
    case SPORTS = 'SPORTS';
    case THEATRE = 'THEATRE';
    case COMEDY = 'COMEDY';
    case CONFERENCE = 'CONFERENCE';
    case FESTIVAL = 'FESTIVAL';
    case EXHIBITION = 'EXHIBITION';
    case OTHER = 'OTHER';

    /** @return string[] */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
