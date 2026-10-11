<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\Event\DomainEvent;
use Doctrine\DBAL\Connection;
use LogicException;

final readonly class DoctrineOutboxEventBus implements EventBus
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            if (!$this->connection->isTransactionActive()) {
                throw new LogicException('Domain events must be recorded within their command transaction');
            }

            $this->connection->insert('outbox_message', [
                'event_id' => $event->eventId(),
                'body' => base64_encode(serialize($event)),
                'created_at' => $event->occurredOn()->format('Y-m-d H:i:s.uP'),
            ]);
        }
    }
}
