<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Repository;

use App\Order\Domain\Entity\Order;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

interface InvoiceRepository
{

    public function findByOrderAndSeller(OrderId $orderId, SellerId $sellerId): ?Order;

    public function save(Order $order): void;
}
