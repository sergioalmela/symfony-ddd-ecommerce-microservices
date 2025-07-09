<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use DateTimeImmutable;

interface DomainEvent
{
    public function eventType(): string;

    public function eventVersion(): int;

    public function aggregateId(): string;

    public function occurredOn(): DateTimeImmutable;

    public function payload(): array;

    public function toArray(): array;
}
