<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Repository;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\SellerId;

class DoctrineOrderRepository implements OrderRepository
{
    /**
     * @return Order[]
     */
    public function findBySeller(SellerId $sellerId): array
    {
        /*        OrderId     $id,
        ProductId   $productId,
        Quantity    $quantity,
        Price       $price,
        CustomerId  $customerId,
        SellerId    $sellerId,
        OrderStatus $status*/
        $id = OrderId::fromPrimitives('123');
        $productId = ProductId::fromPrimitives('456');
        $quantity = Quantity::fromPrimitives(2);
        $price = Price::fromPrimitives(100);
        $customerId = CustomerId::fromPrimitives('789');
        $sellerId = SellerId::fromPrimitives('789');
        $status = OrderStatus::fromPrimitives('CREATED');
        $order = new Order(
            id: $id,
            productId: $productId,
            quantity: $quantity,
            price: $price,
            customerId: $customerId,
            sellerId: $sellerId,
            status: $status
        );
        return [$order];
        return [];
    }

    public function save(Order $order): void
    {
        throw new \RuntimeException('Method not implemented yet.');
    }
}
