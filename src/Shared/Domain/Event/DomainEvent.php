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

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
