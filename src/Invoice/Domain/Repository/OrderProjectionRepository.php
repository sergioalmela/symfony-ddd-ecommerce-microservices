<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Repository;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Shared\Domain\ValueObject\OrderId;

interface OrderProjectionRepository
{
    public function find(OrderId $orderId): ?OrderProjection;

    public function save(OrderProjection $orderProjection): void;
}
