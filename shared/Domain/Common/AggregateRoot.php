<?php

declare(strict_types=1);

namespace Shared\Domain\Common;

class AggregateRoot
{
    private array $events = [];

    final public function releaseEvents(): array
    {
        $domainEvents = $this->events;
        $this->clearRecordedEvents();

        return $domainEvents;
    }

    final public function getRecordedEvents(): array
    {
        return $this->events;
    }

    final public function clearRecordedEvents(): void
    {
        $this->events = [];
    }

    final protected function recordEvent(DomainEvent $domainEvent): void
    {
        $this->events[] = $domainEvent;
    }
}
