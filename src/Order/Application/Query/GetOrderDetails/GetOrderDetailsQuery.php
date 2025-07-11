<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrderDetails;

use App\Shared\Domain\Bus\Query\Query;

final readonly class GetOrderDetailsQuery implements Query
{
    public function __construct(
        public string $id,
        public string $sellerId,
    ) {
    }
}
