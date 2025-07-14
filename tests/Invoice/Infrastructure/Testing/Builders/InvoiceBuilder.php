<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Infrastructure\Testing\Builders;

use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\ValueObject\FilePath;
use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final class InvoiceBuilder
{
    private InvoiceId $invoiceId;
    private OrderId $orderId;
    private SellerId $sellerId;
    private FilePath $filePath;

    private function __construct()
    {
        $this->invoiceId = InvoiceId::generate();
        $this->orderId = OrderId::generate();
        $this->sellerId = SellerId::generate();
        $this->filePath = FilePath::of('/tmp/invoice.pdf');
    }

    public static function anInvoice(): self
    {
        return new self();
    }

    public function withId(InvoiceId $invoiceId): self
    {
        $this->invoiceId = $invoiceId;

        return $this;
    }

    public function withOrderId(OrderId $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function withSellerId(SellerId $sellerId): self
    {
        $this->sellerId = $sellerId;

        return $this;
    }

    public function withFilePath(FilePath $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function build(): Invoice
    {
        return Invoice::create(
            $this->invoiceId,
            $this->orderId,
            $this->sellerId,
            $this->filePath
        );
    }
}
