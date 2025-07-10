<?php

declare(strict_types=1);

namespace App\Invoice\Application\Command\UploadInvoice;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Exception\InvoiceAlreadyExistsException;
use App\Invoice\Domain\Repository\InvoiceRepository;
use App\Invoice\Domain\ValueObject\FilePath;
use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\Service\StorageService;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final readonly class UploadInvoiceCommandHandler implements CommandHandler
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private EventBus $eventBus,
        private StorageService $storageService,
    ) {
    }

    public function __invoke(UploadInvoiceCommand $uploadInvoiceCommand): void
    {
        $orderId = OrderId::of($uploadInvoiceCommand->orderId);
        $sellerId = SellerId::of($uploadInvoiceCommand->sellerId);

        if ($this->invoiceRepository->findByOrderAndSeller($orderId, $sellerId) !== null) {
            throw new InvoiceAlreadyExistsException($uploadInvoiceCommand->orderId);
        }

        $fileName = sprintf('Invoice-%s.%s', $uploadInvoiceCommand->orderId, $uploadInvoiceCommand->fileExtension);
        $fileUrl = $this->storageService->uploadFile(
            $uploadInvoiceCommand->fileContent,
            $fileName
        );

        $invoice = Invoice::create(
            InvoiceId::generate(),
            $orderId,
            $sellerId,
            FilePath::of($fileUrl)
        );

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
