<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrderDetails;

use App\Order\Domain\Entity\Order;

final readonly class GetOrderDetailsResponse
{
    public function __construct(
        public Order $order,
    ) {
    }
}
