<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event;

use App\Invoice\Domain\Event\InvoiceSentEvent;
use App\Invoice\Domain\Event\InvoiceUploadedEvent;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\OrderCreatedEvent;
use App\Shared\Domain\Event\OrderShippedEvent;
use Doctrine\DBAL\Connection;
use LogicException;

final readonly class DoctrineOutboxEventBus implements EventBus
{
    public function __construct(
        private Connection $orderConnection,
        private Connection $invoiceConnection,
    ) {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $connection = match (true) {
                $event instanceof OrderCreatedEvent, $event instanceof OrderShippedEvent => $this->orderConnection,
                $event instanceof InvoiceUploadedEvent, $event instanceof InvoiceSentEvent => $this->invoiceConnection,
                default => throw new LogicException('No outbox connection configured for '.$event::class),
            };

            if (!$connection->isTransactionActive()) {
                throw new LogicException('Domain events must be recorded within their command transaction');
            }

            $connection->insert('outbox_message', [
                'event_id' => $event->eventId(),
                'body' => base64_encode(serialize($event)),
                'created_at' => $event->occurredOn()->format('Y-m-d H:i:s.uP'),
            ]);
        }
    }
}
