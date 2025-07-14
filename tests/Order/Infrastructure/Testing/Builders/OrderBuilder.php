<?php

declare(strict_types=1);

namespace App\Tests\Order\Infrastructure\Testing\Builders;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\SellerId;

final class OrderBuilder
{
    private OrderId $orderId;
    private ProductId $productId;
    private Quantity $quantity;
    private Price $price;
    private CustomerId $customerId;
    private SellerId $sellerId;

    public function __construct()
    {
        $this->orderId = OrderId::generate();
        $this->productId = ProductId::generate();
        $this->quantity = Quantity::of(1);
        $this->price = Price::of(10.50);
        $this->customerId = CustomerId::generate();
        $this->sellerId = SellerId::generate();
    }

    public static function anOrder(): self
    {
        return new self();
    }

    public function withId(OrderId $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function withProductId(ProductId $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function withQuantity(Quantity $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function withPrice(Price $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function withCustomerId(CustomerId $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function withSellerId(SellerId $sellerId): self
    {
        $this->sellerId = $sellerId;

        return $this;
    }

    public function build(): Order
    {
        return Order::create(
            $this->orderId,
            $this->productId,
            $this->quantity,
            $this->price,
            $this->customerId,
            $this->sellerId
        );
    }
}
