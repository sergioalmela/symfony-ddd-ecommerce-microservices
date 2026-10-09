<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Listener;

use App\Invoice\Application\Command\RecordOrderShipment\RecordOrderShipmentCommand;
use App\Invoice\Application\Command\SendInvoice\SendInvoiceCommand;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\Bus\Event\EventHandler;
use App\Shared\Domain\Event\OrderShippedEvent;
use DateTimeImmutable;

final readonly class SendInvoiceOnOrderShipped implements EventHandler
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(OrderShippedEvent $orderShippedEvent): void
    {
        $this->commandBus->dispatch(new RecordOrderShipmentCommand(
            orderId: $orderShippedEvent->aggregateId(),
            sellerId: $orderShippedEvent->sellerId->value(),
            shippedAt: $orderShippedEvent->occurredOn(),
        ));

        $this->commandBus->dispatch(new SendInvoiceCommand(
            $orderShippedEvent->aggregateId(),
            new DateTimeImmutable()
        ));
    }
}
