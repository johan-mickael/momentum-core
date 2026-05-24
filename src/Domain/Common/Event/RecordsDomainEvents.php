<?php

declare(strict_types=1);

namespace App\Domain\Common\Event;

trait RecordsDomainEvents
{
    /** @var DomainEventInterface[] */
    private array $recordedEvents = [];

    protected function recordEvent(DomainEventInterface $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /** @return DomainEventInterface[] */
    public function pullRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
