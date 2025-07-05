<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Event\BaseDomainEvent;
use App\Shared\Domain\ValueObject\CustomerId;
use DateTimeImmutable;

final readonly class OrderCreatedEvent extends BaseDomainEvent
{
    public const EVENT_TYPE = 'order.created';
    public const EVENT_VERSION = 1;

    private function __construct(
        string            $aggregateId,
        DateTimeImmutable $occurredOn,
        public CustomerId $customerId,
        public Price      $price,
        public Quantity   $quantity,
    ) {
        parent::__construct($aggregateId, $occurredOn);
    }

    public static function create(
        OrderId            $aggregateId,
        CustomerId         $customerId,
        Price              $price,
        Quantity           $quantity,
        ?DateTimeImmutable $occurredOn = null
    ): self {
        return new self(
            $aggregateId->value(),
            $occurredOn ?? new DateTimeImmutable(),
            $customerId,
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
            'customerId' => $this->customerId->value(),
            'price' => $this->price->value(),
            'quantity' => $this->quantity->value(),
        ];
    }
}
