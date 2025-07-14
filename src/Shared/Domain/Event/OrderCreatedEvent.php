<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final readonly class OrderCreatedEvent extends BaseDomainEvent
{
    public const string EVENT_TYPE = 'order.created';
    public const int EVENT_VERSION = 1;

    private function __construct(
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        public CustomerId $customerId,
        public SellerId $sellerId,
        public Price $price,
        public Quantity $quantity,
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function create(
        OrderId $orderId,
        CustomerId $customerId,
        SellerId $sellerId,
        Price $price,
        Quantity $quantity,
        ?DateTimeImmutable $occurredOn = null,
    ): self {
        return new self(
            $orderId->value(),
            $occurredOn ?? new DateTimeImmutable(),
            $customerId,
            $sellerId,
            $price,
            $quantity
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
            'sellerId' => $this->sellerId->value(),
            'price' => $this->price->value(),
            'quantity' => $this->quantity->value(),
        ];
    }
}
