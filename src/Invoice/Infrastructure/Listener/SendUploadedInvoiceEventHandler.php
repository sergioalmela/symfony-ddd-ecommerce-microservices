<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Listener;

use App\Invoice\Application\Command\SendInvoice\SendInvoiceCommand;
use App\Invoice\Domain\Event\InvoiceUploadedEvent;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\Bus\Event\EventHandler;
use DateTimeImmutable;

final readonly class SendUploadedInvoiceEventHandler implements EventHandler
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    public function __invoke(InvoiceUploadedEvent $invoiceUploadedEvent): void
    {
        $this->commandBus->dispatch(new SendInvoiceCommand($invoiceUploadedEvent->orderId(), new DateTimeImmutable()));
    }
}
