<?php

declare(strict_types=1);

namespace App\Invoice\Application\Command\UploadInvoice;

use App\Shared\Domain\Bus\Command\Command;

final readonly class UploadInvoiceCommand implements Command
{
    public function __construct(
        public string $orderId,
        public string $sellerId,
        public string $fileContent,
        public string $mimeType,
    ) {
    }
}
