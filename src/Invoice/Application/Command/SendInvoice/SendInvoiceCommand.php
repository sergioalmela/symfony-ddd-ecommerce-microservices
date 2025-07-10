<?php

declare(strict_types=1);

namespace App\Invoice\Application\Command\SendInvoice;

use App\Shared\Domain\Bus\Command\Command;
use DateTimeImmutable;

final readonly class SendInvoiceCommand implements Command
{
    public function __construct(
        public string $orderId,
        public DateTimeImmutable $date,
    ) {
    }
}
