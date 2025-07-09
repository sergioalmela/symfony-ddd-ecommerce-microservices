<?php

declare(strict_types=1);

namespace App\Invoice\Application\EventHandler;

use App\Order\Domain\Event\OrderShippedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class OrderShippedEventHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrderShippedEvent $orderShippedEvent): void
    {
        $this->logger->info('Order shipped event received', [
            'orderId' => $orderShippedEvent->aggregateId(),
            'customerId' => $orderShippedEvent->customerId->value(),
            'eventType' => $orderShippedEvent->eventType(),
            'occurredOn' => $orderShippedEvent->occurredOn()->format('Y-m-d H:i:s'),
        ]);

        // TODO: Implement invoice generation logic here
        // For now, just log the event
        $this->logger->info('Invoice generation triggered for order', [
            'orderId' => $orderShippedEvent->aggregateId(),
            'customerId' => $orderShippedEvent->customerId->value(),
        ]);
    }
}
