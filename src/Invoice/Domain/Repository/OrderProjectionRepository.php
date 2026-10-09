<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Repository;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Shared\Domain\ValueObject\OrderId;

interface OrderProjectionRepository
{
    /**
     * Locks an existing projection until the current transaction ends, so shipment, upload and
     * sending for one order run one at a time. A missing projection is not locked: only save()
     * may create it, and it is safe to run concurrently.
     */
    public function findForUpdate(OrderId $orderId): ?OrderProjection;

    public function save(OrderProjection $orderProjection): void;
}
