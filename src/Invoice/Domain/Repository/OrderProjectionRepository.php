<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Repository;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Shared\Domain\ValueObject\OrderId;

interface OrderProjectionRepository
{
    public function save(OrderProjection $orderProjection): void;

    public function findByOrderId(OrderId $orderId): ?OrderProjection;

    public function update(OrderProjection $orderProjection): void;
}
