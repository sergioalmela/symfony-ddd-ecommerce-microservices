<?php

declare(strict_types=1);

namespace App\Order\Application\Command\UpdateOrderStatus;

use App\Shared\Domain\Bus\Command\Command;

final readonly class UpdateOrderStatusCommand implements Command
{
    public function __construct(
        public string $id,
        public string $sellerId,
        public string $status,
    ) {
    }
}
