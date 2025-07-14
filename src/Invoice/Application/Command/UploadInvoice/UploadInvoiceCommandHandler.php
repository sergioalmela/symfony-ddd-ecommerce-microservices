<?php

declare(strict_types=1);

namespace App\Invoice\Application\Command\UploadInvoice;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Invoice\Domain\Exception\InvoiceAlreadyExistsException;
use App\Invoice\Domain\Repository\InvoiceRepository;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Invoice\Domain\Service\InvoiceFileValidator;
use App\Invoice\Domain\ValueObject\FilePath;
use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\Exception\OrderNotFoundException;
use App\Shared\Domain\Service\StorageService;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use InvalidArgumentException;

final readonly class UploadInvoiceCommandHandler implements CommandHandler
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private OrderProjectionRepository $orderProjectionRepository,
        private EventBus $eventBus,
        private StorageService $storageService,
        private InvoiceFileValidator $invoiceFileValidator,
    ) {
    }

    public function __invoke(UploadInvoiceCommand $uploadInvoiceCommand): void
    {
        $orderId = OrderId::of($uploadInvoiceCommand->orderId);
        $sellerId = SellerId::of($uploadInvoiceCommand->sellerId);

        if ($this->invoiceRepository->findByOrderAndSeller($orderId, $sellerId) instanceof Invoice) {
            throw new InvoiceAlreadyExistsException($uploadInvoiceCommand->orderId);
        }

        if (!$this->orderProjectionRepository->find($orderId) instanceof OrderProjection) {
            throw new OrderNotFoundException($uploadInvoiceCommand->orderId);
        }

        $this->ensureValidInvoiceFile($uploadInvoiceCommand->fileContent);
        $this->invoiceFileValidator->validate($uploadInvoiceCommand->mimeType);

        $fileName = \sprintf('invoice-%s-order-%s.pdf', InvoiceId::generate()->value(), $uploadInvoiceCommand->orderId);
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

    private function ensureValidInvoiceFile(string $fileContent): void
    {
        if ('' === $fileContent || '0' === $fileContent) {
            throw new InvalidArgumentException('Invoice file content cannot be empty');
        }
    }

    private function publishDomainEvents(Invoice $invoice): void
    {
        $events = $invoice->releaseEvents();

        foreach ($events as $event) {
            $this->eventBus->publish($event);
        }
    }
}
