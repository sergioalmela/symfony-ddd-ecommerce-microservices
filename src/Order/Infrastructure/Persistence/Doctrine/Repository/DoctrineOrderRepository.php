<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Repository;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\OrderConcurrentModificationException;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineRepository;
use Doctrine\ORM\OptimisticLockException;

final class DoctrineOrderRepository extends DoctrineRepository implements OrderRepository
{
    public function find(OrderId $orderId): ?Order
    {
        return $this->repository(Order::class)->find($orderId);
    }

    public function findByIdAndSeller(OrderId $orderId, SellerId $sellerId): ?Order
    {
        return $this->repository(Order::class)->findOneBy([
            'orderId' => $orderId,
            'sellerId' => $sellerId,
        ]);
    }

    /**
     * @return Order[]
     */
    public function findBySeller(SellerId $sellerId): array
    {
        return $this->repository(Order::class)->findBy([
            'sellerId' => $sellerId,
        ]);
    }

    public function save(Order $order): void
    {
        try {
            $this->persist($order);
        } catch (OptimisticLockException $optimisticLockException) {
            throw new OrderConcurrentModificationException($order->toPrimitives()['id'], $optimisticLockException);
        }
    }
}
