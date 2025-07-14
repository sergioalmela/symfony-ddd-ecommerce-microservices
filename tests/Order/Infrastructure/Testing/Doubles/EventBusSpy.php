<?php

declare(strict_types=1);

namespace App\Tests\Order\Infrastructure\Testing\Doubles;

use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\Event\DomainEvent;

final class EventBusSpy implements EventBus
{
    private array $publishedEvents = [];

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->publishedEvents[] = $event;
        }
    }

    public function domainEvents(): array
    {
        return $this->publishedEvents;
    }

    public function clean(): void
    {
        $this->publishedEvents = [];
    }
}
