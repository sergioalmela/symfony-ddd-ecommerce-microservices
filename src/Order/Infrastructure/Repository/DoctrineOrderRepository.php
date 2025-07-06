<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Repository;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepository;

class DoctrineOrderRepository implements OrderRepository
{
    public function save(Order $order): void
    {
        throw new \RuntimeException('Method not implemented yet.');
    }
}
