<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrders;

use App\Shared\Domain\Bus\Query\Query;

final readonly class GetOrdersQuery implements Query
{
    public function __construct(
        public string $sellerId,
    ) {
    }
}
