<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Infrastructure\Testing\Doubles;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Repository\InvoiceRepository;
use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final class InvoiceRepositorySpy implements InvoiceRepository
{
    private array $toReturn = [];
    private bool $hasChanges = false;
    private array $invoiceStored = [];

    public function add(Invoice $invoice): void
    {
        $this->toReturn[] = $invoice;
    }

    public function storeChanged(): bool
    {
        return $this->hasChanges;
    }

    public function stored(): array
    {
        return $this->invoiceStored;
    }

    public function clean(): void
    {
        $this->toReturn = [];
        $this->hasChanges = false;
        $this->invoiceStored = [];
    }

    public function find(InvoiceId $invoiceId): ?Invoice
    {
        foreach ($this->toReturn as $invoice) {
            if ($invoice->id()->equals($invoiceId)) {
                return $invoice;
            }
        }

        return null;
    }

    public function findByOrder(OrderId $orderId): ?Invoice
    {
        foreach ($this->toReturn as $invoice) {
            if ($invoice->orderId()->equals($orderId)) {
                return $invoice;
            }
        }

        return null;
    }

    public function findByOrderAndSeller(OrderId $orderId, SellerId $sellerId): ?Invoice
    {
        foreach ($this->toReturn as $invoice) {
            if ($invoice->orderId()->equals($orderId) && $invoice->sellerId()->equals($sellerId)) {
                return $invoice;
            }
        }

        return null;
    }

    public function save(Invoice $invoice): void
    {
        $this->hasChanges = true;
        $this->invoiceStored[] = $invoice;
    }
}
