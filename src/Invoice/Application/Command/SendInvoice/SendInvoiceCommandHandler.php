<?php

declare(strict_types=1);

namespace App\Invoice\Application\Command\SendInvoice;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Exception\InvoiceNotFoundException;
use App\Invoice\Domain\Repository\InvoiceRepository;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\ValueObject\OrderId;

final readonly class SendInvoiceCommandHandler implements CommandHandler
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(SendInvoiceCommand $sendInvoiceCommand): void
    {
        $orderId = OrderId::of($sendInvoiceCommand->orderId);

        $invoice = $this->invoiceRepository->findByOrder($orderId);
        if ($invoice === null) {
            throw new InvoiceNotFoundException($sendInvoiceCommand->orderId);
        }

        $invoice->send($sendInvoiceCommand->date);

        $this->invoiceRepository->save($invoice);

        $this->publishDomainEvents($invoice);
    }

    private function publishDomainEvents(Invoice $invoice): void
    {
        $events = $invoice->releaseEvents();

        foreach ($events as $event) {
            $this->eventBus->publish($event);
        }
    }
}
