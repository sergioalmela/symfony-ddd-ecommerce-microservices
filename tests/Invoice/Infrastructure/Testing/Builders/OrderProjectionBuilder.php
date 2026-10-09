<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Infrastructure\Testing\Builders;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final class OrderProjectionBuilder
{
    private OrderId $orderId;
    private SellerId $sellerId;
    private ?DateTimeImmutable $shippedAt = null;

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

    public function withShippedAt(DateTimeImmutable $shippedAt): self
    {
        $this->shippedAt = $shippedAt;

        return $this;
    }

    public function build(): OrderProjection
    {
        return new OrderProjection(
            $this->orderId,
            $this->sellerId,
            $this->shippedAt
        );
    }
}
