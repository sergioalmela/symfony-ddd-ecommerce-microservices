<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Repository;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

interface InvoiceRepository
{
    public function find(InvoiceId $invoiceId): ?Invoice;

    public function findByOrder(OrderId $orderId): ?Invoice;

    public function findByOrderAndSeller(OrderId $orderId, SellerId $sellerId): ?Invoice;

    public function save(Invoice $invoice): void;
}
