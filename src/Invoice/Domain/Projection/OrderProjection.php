<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Projection;

use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;

final class OrderProjection
{
    public function __construct(
        private readonly OrderId $orderId,
        private readonly CustomerId $customerId,
        private readonly string $status,
        private readonly float $totalAmount,
    ) {
    }

    public static function fromPrimitives(
        string $orderId,
        string $customerId,
        string $status,
        float $totalAmount,
    ): self {
        return new self(
            OrderId::of($orderId),
            CustomerId::of($customerId),
            $status,
            $totalAmount,
        );
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function customerId(): CustomerId
    {
        return $this->customerId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function totalAmount(): float
    {
        return $this->totalAmount;
    }

    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    public function toPrimitives(): array
    {
        return [
            'orderId' => $this->orderId->value(),
            'customerId' => $this->customerId->value(),
            'status' => $this->status,
            'totalAmount' => $this->totalAmount,
        ];
    }
}
