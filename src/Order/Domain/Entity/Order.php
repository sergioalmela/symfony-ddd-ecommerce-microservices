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
    public function __construct(private readonly OrderId $id, private readonly ProductId $productId, private readonly Quantity $quantity, private readonly Price $price, private readonly CustomerId $customerId, private readonly SellerId $sellerId, private OrderStatus $status)
    {
    }

    public static function create(
        OrderId $id,
        ProductId $productId,
        Quantity $quantity,
        Price $price,
        CustomerId $customerId,
        SellerId $sellerId,
    ): self {
        $order = new self(
            $id,
            $productId,
            $quantity,
            $price,
            $customerId,
            $sellerId,
            OrderStatus::created()
        );

        $order->recordEvent(OrderCreatedEvent::create($id, $customerId, $price, $quantity));

        return $order;
    }

    public function updateStatus(OrderStatus $status): void
    {
        if ($this->hasSameStatus($status)) {
            return;
        }

        $this->status = $status;

        if ($status->isShipped()) {
            $this->recordEvent(OrderShippedEvent::create(
                $this->id,
                $this->customerId,
            ));
        }
    }

    private function hasSameStatus(OrderStatus $status): bool
    {
        return $this->status->equals($status);
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id->value(),
            'productId' => $this->productId->value(),
            'quantity' => $this->quantity->value(),
            'price' => $this->price->value(),
            'customerId' => $this->customerId->value(),
            'sellerId' => $this->sellerId->value(),
            'status' => $this->status->value(),
        ];
    }
}
