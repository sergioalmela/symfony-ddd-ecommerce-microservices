<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use DateTimeImmutable;
use DateTimeInterface;

abstract readonly class BaseDomainEvent implements DomainEvent
{
    public function __construct(
        protected string $aggregateId,
        protected DateTimeImmutable $occurredOn,
    ) {
    }

    abstract public function eventType(): string;

    abstract public function eventVersion(): int;

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
            'eventType' => $this->eventType(),
            'eventVersion' => $this->eventVersion(),
            'aggregateId' => $this->aggregateId(),
            'payload' => $this->payload(),
            'occurredOn' => $this->occurredOn()->format(DateTimeInterface::ATOM),
        ];
    }
}
