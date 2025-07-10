<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Doctrine\Repository;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Repository\InvoiceRepository;
use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineRepository;

final class DoctrineInvoiceRepository extends DoctrineRepository implements InvoiceRepository
{
    public function find(InvoiceId $invoiceId): ?Invoice
    {
        return $this->repository(Invoice::class)->find($invoiceId);
    }

    public function findByOrderAndSeller(OrderId $orderId, SellerId $sellerId): ?Invoice
    {
        return $this->repository(Invoice::class)->findOneBy([
            'orderId' => $orderId,
            'sellerId' => $sellerId,
        ]);
    }

    public function save(Invoice $invoice): void
    {
        $this->persist($invoice);
    }
}