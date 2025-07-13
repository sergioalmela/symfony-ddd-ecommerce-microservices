<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Entity\Projection;

use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final readonly class OrderProjection
{
    public function __construct(
        private OrderId $orderId,
        private SellerId $sellerId,
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
}
