<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use App\Order\Domain\Exception\OrderStatusInvalidException;

enum OrderStatusType: string
{
    case CREATED = 'CREATED';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case SHIPPING_IN_PROGRESS = 'SHIPPING_IN_PROGRESS';
    case SHIPPED = 'SHIPPED';
}

final readonly class OrderStatus
{
    private function __construct(
        private OrderStatusType $value
    ) {}

    public static function of(string $value): self
    {
        $status = OrderStatusType::tryFrom($value);

        if ($status === null) {
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
        return $this->value === OrderStatusType::SHIPPED;
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this->value) {
            OrderStatusType::CREATED => in_array($newStatus->value, [
                OrderStatusType::ACCEPTED,
                OrderStatusType::REJECTED
            ]),
            OrderStatusType::ACCEPTED => in_array($newStatus->value, [
                OrderStatusType::SHIPPING_IN_PROGRESS
            ]),
            OrderStatusType::SHIPPING_IN_PROGRESS => in_array($newStatus->value, [
                OrderStatusType::SHIPPED
            ]),
            OrderStatusType::REJECTED,
            OrderStatusType::SHIPPED => false,
        };
    }

    public function value(): string
    {
        return $this->value->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value->value;
    }
}
