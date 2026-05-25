<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Event\ValueObject\EventId;
use App\Domain\Event\ValueObject\EventVisibility;

interface EventRepositoryInterface
{
    public function create(Event $event): void;
    public function save(Event $event): void;
    public function getById(EventId $id): Event;
    public function remove(Event $event): void;

    /** @return Event[] */
    public function list(EventVisibility $visibility): array;
}
