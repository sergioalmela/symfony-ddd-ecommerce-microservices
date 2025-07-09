<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Repository;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineRepository;

final class DoctrineOrderRepository extends DoctrineRepository implements OrderRepository
{
    public function find(OrderId $orderId): ?Order
    {
        return $this->repository(Order::class)->find($orderId);
    }

    public function findByIdAndSeller(OrderId $orderId, SellerId $sellerId): ?Order
    {
        return $this->repository(Order::class)->findOneBy([
            'id' => $orderId,
            'sellerId' => $sellerId
        ]);
    }

    /**
     * @return Order[]
     */
    public function findBySeller(SellerId $sellerId): array
    {
        return $this->repository(Order::class)->findBy([
            'sellerId' => $sellerId
        ]);
    }

    public function save(Order $order): void
    {
        $this->persist($order);
    }
}
