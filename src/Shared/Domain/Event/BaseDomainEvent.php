<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Shared\Domain\ValueObject\EventId;
use DateTimeImmutable;
use DateTimeInterface;

abstract readonly class BaseDomainEvent implements DomainEvent
{
    private string $eventId;

    public function __construct(
        protected string $aggregateId,
        protected DateTimeImmutable $occurredOn,
    ) {
        $this->eventId = EventId::generate()->value();
    }

    abstract public function eventType(): string;

    abstract public function eventVersion(): int;

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId(),
            'eventType' => $this->eventType(),
            'eventVersion' => $this->eventVersion(),
            'aggregateId' => $this->aggregateId(),
            'payload' => $this->payload(),
            'occurredOn' => $this->occurredOn()->format(DateTimeInterface::ATOM),
        ];
    }
}
