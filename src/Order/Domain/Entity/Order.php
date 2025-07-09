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
    private OrderId $id;
    private ProductId $productId;
    private Quantity $quantity;
    private Price $price;
    private CustomerId $customerId;
    private SellerId $sellerId;
    private OrderStatus $status;

    public function __construct(
        OrderId     $id,
        ProductId   $productId,
        Quantity    $quantity,
        Price       $price,
        CustomerId  $customerId,
        SellerId    $sellerId,
        OrderStatus $status
    ) {
        $this->id = $id;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->customerId = $customerId;
        $this->sellerId = $sellerId;
        $this->status = $status;
    }

    public static function create(
        OrderId    $id,
        ProductId  $productId,
        Quantity   $quantity,
        Price      $price,
        CustomerId $customerId,
        SellerId   $sellerId
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
