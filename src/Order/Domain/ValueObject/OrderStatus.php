<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use App\Order\Domain\Exception\OrderStatusInvalidException;
use Stringable;

enum OrderStatusType: string
{
    case CREATED = 'CREATED';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case SHIPPING_IN_PROGRESS = 'SHIPPING_IN_PROGRESS';
    case SHIPPED = 'SHIPPED';
}

final readonly class OrderStatus implements Stringable
{
    private function __construct(
        private OrderStatusType $orderStatusType,
    ) {
    }

    public static function of(string $value): self
    {
        $status = OrderStatusType::tryFrom($value);

        if (null === $status) {
            throw new OrderStatusInvalidException($value);
        }

        return new self($status);
    }

    public static function fromPrimitives(string $value): self
    {
        return new self(OrderStatusType::from($value));
    }

    public static function created(): self
    {
        return new self(OrderStatusType::CREATED);
    }

    public function isShipped(): bool
    {
        return OrderStatusType::SHIPPED === $this->orderStatusType;
    }

    public function value(): string
    {
        return $this->orderStatusType->value;
    }

    public function equals(self $other): bool
    {
        return $this->orderStatusType === $other->orderStatusType;
    }

    public function __toString(): string
    {
        return $this->orderStatusType->value;
    }
}
