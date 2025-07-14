<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use DateTimeImmutable;

final readonly class OrderShippedEvent extends BaseDomainEvent
{
    public const string EVENT_TYPE = 'order.shipped';
    public const int EVENT_VERSION = 1;

    private function __construct(
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        public CustomerId $customerId,
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function create(
        OrderId $orderId,
        CustomerId $customerId,
        ?DateTimeImmutable $occurredOn = null,
    ): self {
        return new self(
            $orderId->value(),
            $occurredOn ?? new DateTimeImmutable(),
            $customerId
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
            'orderId' => $this->aggregateId(),
            'customerId' => $this->customerId->value(),
        ];
    }
}
