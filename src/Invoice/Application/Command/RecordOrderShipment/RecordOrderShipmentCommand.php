<?php

declare(strict_types=1);

namespace App\Invoice\Application\Command\RecordOrderShipment;

use App\Shared\Domain\Bus\Command\Command;
use DateTimeImmutable;

final readonly class RecordOrderShipmentCommand implements Command
{
    public function __construct(
        public string $orderId,
        public string $sellerId,
        public DateTimeImmutable $shippedAt,
    ) {
    }
}
