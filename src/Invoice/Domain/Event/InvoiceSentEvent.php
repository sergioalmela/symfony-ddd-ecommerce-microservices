<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Event;

use App\Invoice\Domain\ValueObject\InvoiceId;
use App\Shared\Domain\Event\BaseDomainEvent;
use DateTimeImmutable;

final readonly class InvoiceSentEvent extends BaseDomainEvent
{
    public const string EVENT_TYPE = 'invoice.sent';
    public const int EVENT_VERSION = 1;

    private function __construct(
        string $aggregateId,
        DateTimeImmutable $occurredOn,
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function create(
        InvoiceId $invoiceId,
        ?DateTimeImmutable $occurredOn = null,
    ): self {
        return new self(
            $invoiceId->value(),
            $occurredOn ?? new DateTimeImmutable(),
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
        ];
    }
}
