<?php

declare(strict_types=1);

namespace App\Order\Domain\Repository;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

interface OrderRepository {
    public function find(OrderId $orderId): ?Order;
    public function findByIdAndSeller(OrderId $orderId, SellerId $sellerId): ?Order;
    public function findBySeller(SellerId $sellerId): array;
    public function save(Order $order): void;
}
