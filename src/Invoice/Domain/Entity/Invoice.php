<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Entity;

use App\Invoice\Domain\Event\InvoiceSentEvent;
use App\Invoice\Domain\Event\InvoiceUploadedEvent;
use App\Invoice\Domain\ValueObject\FilePath;
use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Invoice\Domain\ValueObject\SentAt;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final class Invoice extends AggregateRoot
{
    public function __construct(
        private readonly InvoiceId $invoiceId,
        private readonly OrderId $orderId,
        private readonly SellerId $sellerId,
        private readonly FilePath $filePath,
        private ?SentAt $sentAt = null,
    ) {
    }

    public static function create(
        InvoiceId $invoiceId,
        OrderId $orderId,
        SellerId $sellerId,
        FilePath $filePath,
    ): self {
        $invoice = new self(
            $invoiceId,
            $orderId,
            $sellerId,
            $filePath,
        );

        $invoice->recordEvent(InvoiceUploadedEvent::create(
            $invoiceId,
            $orderId,
            $sellerId,
            $filePath->value()
        ));

        return $invoice;
    }

    public function send(DateTimeImmutable $date): void
    {
        $this->sentAt = SentAt::of($date);

        $this->recordEvent(InvoiceSentEvent::create($this->invoiceId));
    }

    public function isSent(): bool
    {
        return $this->sentAt instanceof SentAt;
    }

    public function id(): InvoiceId
    {
        return $this->invoiceId;
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function sellerId(): SellerId
    {
        return $this->sellerId;
    }

    public function filePath(): FilePath
    {
        return $this->filePath;
    }

    public function sentAt(): ?SentAt
    {
        return $this->sentAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return [
            'id' => $this->invoiceId->value(),
            'orderId' => $this->orderId->value(),
            'sellerId' => $this->sellerId->value(),
            'filePath' => $this->filePath->value(),
            'sentAt' => $this->sentAt?->format(),
        ];
    }
}
