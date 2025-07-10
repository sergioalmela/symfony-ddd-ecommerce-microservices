<?php

declare(strict_types=1);

namespace App\Order\Domain\Entity;

use App\Order\Domain\Event\OrderCreatedEvent;
use App\Order\Domain\Event\OrderShippedEvent;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\SellerId;

final class Order extends AggregateRoot
{
    public function __construct(
        private readonly OrderId $orderId,
        private readonly ProductId $productId,
        private readonly Quantity $quantity,
        private readonly Price $price,
        private readonly CustomerId $customerId,
        private readonly SellerId $sellerId,
        private OrderStatus $orderStatus)
    {
    }

    public static function create(
        OrderId $orderId,
        ProductId $productId,
        Quantity $quantity,
        Price $price,
        CustomerId $customerId,
        SellerId $sellerId,
    ): self {
        $order = new self(
            $orderId,
            $productId,
            $quantity,
            $price,
            $customerId,
            $sellerId,
            OrderStatus::created()
        );

        $order->recordEvent(OrderCreatedEvent::create($orderId, $customerId, $sellerId, $price, $quantity));

        return $order;
    }

    public function updateStatus(OrderStatus $orderStatus): void
    {
        if ($this->hasSameStatus($orderStatus)) {
            return;
        }

        $this->orderStatus = $orderStatus;

        if ($orderStatus->isShipped()) {
            $this->recordEvent(OrderShippedEvent::create(
                $this->orderId,
                $this->customerId,
            ));
        }
    }

    private function hasSameStatus(OrderStatus $orderStatus): bool
    {
        return $this->orderStatus->equals($orderStatus);
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->orderId->value(),
            'productId' => $this->productId->value(),
            'quantity' => $this->quantity->value(),
            'price' => $this->price->value(),
            'customerId' => $this->customerId->value(),
            'sellerId' => $this->sellerId->value(),
            'status' => $this->orderStatus->value(),
        ];
    }
}
