<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Listener;

use App\Invoice\Application\Command\SendInvoice\SendInvoiceCommand;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\Bus\Event\EventHandler;
use App\Shared\Domain\Event\OrderShippedEvent;
use App\Shared\Domain\ValueObject\OrderId;
use DateTimeImmutable;

final readonly class SendInvoiceToCustomerEventHandler implements EventHandler
{
    public function __construct(
        private CommandBus $commandBus,
        private OrderProjectionRepository $orderProjectionRepository,
    ) {
    }

    public function __invoke(OrderShippedEvent $orderShippedEvent): void
    {
        $this->orderProjectionRepository->recordShipment(
            OrderId::of($orderShippedEvent->aggregateId()),
            $orderShippedEvent->occurredOn()
        );

        $now = new DateTimeImmutable();

        $sendInvoiceCommand = new SendInvoiceCommand(
            $orderShippedEvent->aggregateId(),
            $now
        );

        $this->commandBus->dispatch($sendInvoiceCommand);
    }
}
