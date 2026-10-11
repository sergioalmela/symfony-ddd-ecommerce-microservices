<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event;

use App\Invoice\Domain\Event\InvoiceSentEvent;
use App\Invoice\Domain\Event\InvoiceUploadedEvent;
use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\OrderCreatedEvent;
use App\Shared\Domain\Event\OrderShippedEvent;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\SellerId;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use UnexpectedValueException;

final readonly class OutboxPublisher
{
    public function __construct(private MessengerEventBus $messengerEventBus)
    {
    }

    public function publishNext(Connection $connection): bool
    {
        return $connection->transactional(function () use ($connection): bool {
            $message = $connection->fetchAssociative('SELECT event_id, body FROM outbox_message ORDER BY sequence LIMIT 1 FOR UPDATE SKIP LOCKED');
            if (false === $message) {
                return false;
            }

            $body = base64_decode((string) $message['body'], true);
            if (false === $body) {
                throw new UnexpectedValueException('Invalid outbox message encoding');
            }

            $event = unserialize($body, ['allowed_classes' => [
                OrderCreatedEvent::class,
                OrderShippedEvent::class,
                InvoiceUploadedEvent::class,
                InvoiceSentEvent::class,
                CustomerId::class,
                SellerId::class,
                Price::class,
                Quantity::class,
                DateTimeImmutable::class,
            ]]);

            if (!$event instanceof DomainEvent || $event->eventId() !== $message['event_id']) {
                throw new UnexpectedValueException('Invalid outbox domain event');
            }

            $this->messengerEventBus->publish($event);
            $connection->delete('outbox_message', ['event_id' => $message['event_id']]);

            return true;
        });
    }
}
