<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Repository;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineRepository;
use Doctrine\DBAL\LockMode;

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

    public function findByIdAndSellerForUpdate(OrderId $orderId, SellerId $sellerId): ?Order
    {
        return $this->repository(Order::class)->createQueryBuilder('orderEntity')
            ->where('orderEntity.orderId = :orderId')
            ->andWhere('orderEntity.sellerId = :sellerId')
            ->setParameter('orderId', $orderId)
            ->setParameter('sellerId', $sellerId)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
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
        $this->persist($order);
    }
}
