<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Infrastructure\Testing\Builders;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final class OrderProjectionBuilder
{
    private OrderId $orderId;
    private SellerId $sellerId;

    private function __construct()
    {
        $this->orderId = OrderId::generate();
        $this->sellerId = SellerId::generate();
    }

    public static function anOrderProjection(): self
    {
        return new self();
    }

    public function withOrderId(OrderId $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function withSellerId(SellerId $sellerId): self
    {
        $this->sellerId = $sellerId;

        return $this;
    }

    public function build(): OrderProjection
    {
        return OrderProjection::create(
            $this->orderId->value(),
            $this->sellerId->value()
        );
    }
}
