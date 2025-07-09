<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Entity;

use App\Invoice\Domain\ValueObject\FilePath;
use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Invoice\Domain\ValueObject\SentAt;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final class Invoice extends AggregateRoot
{
    public function __construct(private readonly InvoiceId $id, private readonly OrderId $orderId, private readonly SellerId $sellerId, private readonly FilePath $filePath, private ?SentAt $sentAt = null)
    {
    }

    public static function create(
        InvoiceId $id,
        OrderId $orderId,
        SellerId $sellerId,
        FilePath $filePath,
    ): self {
        return new self(
            $id,
            $orderId,
            $sellerId,
            $filePath
        );
    }

    public function markAsSent(): void
    {
        if ($this->sentAt instanceof SentAt) {
            return;
        }

        $this->sentAt = SentAt::now();
    }

    public function isSent(): bool
    {
        return $this->sentAt instanceof SentAt;
    }

    public function id(): InvoiceId
    {
        return $this->id;
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

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id->value(),
            'orderId' => $this->orderId->value(),
            'sellerId' => $this->sellerId->value(),
            'filePath' => $this->filePath->value(),
            'sentAt' => $this->sentAt?->format(),
        ];
    }
}
