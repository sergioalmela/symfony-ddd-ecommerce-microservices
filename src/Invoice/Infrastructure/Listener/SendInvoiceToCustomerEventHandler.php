<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Listener;

use App\Invoice\Application\Command\SendInvoice\SendInvoiceCommand;
use App\Order\Domain\Event\OrderShippedEvent;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\Bus\Event\EventHandler;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendInvoiceToCustomerEventHandler implements EventHandler
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(OrderShippedEvent $orderShippedEvent): void
    {
        $now = new DateTimeImmutable();

        $command = new SendInvoiceCommand(
            $orderShippedEvent->aggregateId(),
            $now
        );

        $this->commandBus->dispatch($command);
    }
}
