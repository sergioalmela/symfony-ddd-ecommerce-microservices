<?php

declare(strict_types=1);

namespace App\Order\Application\Command\CreateOrder;

use App\Shared\Domain\Bus\Command\Command;

final readonly class CreateOrderCommand implements Command
{
    public function __construct(
        public string $id,
        public string $productId,
        public int $quantity,
        public float $price,
        public string $customerId,
        public string $sellerId,
    ) {
    }
}
