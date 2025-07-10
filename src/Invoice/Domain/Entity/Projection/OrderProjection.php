<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Entity\Projection;

use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final class OrderProjection
{
    public function __construct(
        private readonly OrderId $orderId,
        private readonly SellerId $sellerId,
    ) {
    }

    public static function create(
        string $orderId,
        string $sellerId,
    ): self {
        return new self(
            OrderId::of($orderId),
            SellerId::of($sellerId)
        );
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function sellerId(): SellerId
    {
        return $this->sellerId;
    }

    public function status(): string
    {
        return $this->status;
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
