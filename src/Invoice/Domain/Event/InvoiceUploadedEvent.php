<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Event;

use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Shared\Domain\Event\BaseDomainEvent;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final readonly class InvoiceUploadedEvent extends BaseDomainEvent
{
    public const string EVENT_TYPE = 'invoice.uploaded';
    public const int EVENT_VERSION = 1;

    private function __construct(
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        private string $orderId,
        private string $sellerId,
        private string $filePath,
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function create(
        InvoiceId $invoiceId,
        OrderId $orderId,
        SellerId $sellerId,
        string $filePath,
        ?DateTimeImmutable $occurredOn = null,
    ): self {
        return new self(
            $invoiceId->value(),
            $occurredOn ?? new DateTimeImmutable(),
            $orderId->value(),
            $sellerId->value(),
            $filePath,
        );
    }

    public function eventType(): string
    {
        return self::EVENT_TYPE;
    }

    public function eventVersion(): int
    {
        return self::EVENT_VERSION;
    }

    public function payload(): array
    {
        return [
            'invoiceId' => $this->aggregateId(),
            'orderId' => $this->orderId,
            'sellerId' => $this->sellerId,
            'filePath' => $this->filePath,
        ];
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function sellerId(): string
    {
        return $this->sellerId;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }
}
