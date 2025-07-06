<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrders;

final readonly class GetOrdersResponse
{
    public function __construct(
        public array $orders,
    ) {
    }
}
