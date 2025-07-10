<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entity;

use App\Shared\Domain\Event\DomainEvent;

abstract class AggregateRoot
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

    final public function hasRecordedEvents(): bool
    {
        return [] !== $this->events;
    }

    final protected function recordEvent(DomainEvent $domainEvent): void
    {
        $this->events[] = $domainEvent;
    }

    abstract public function toPrimitives(): array;
}
