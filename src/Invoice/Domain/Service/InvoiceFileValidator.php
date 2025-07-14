<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Service;

use App\Invoice\Domain\Exception\InvalidInvoiceFileTypeException;

final readonly class InvoiceFileValidator
{
    private const array ALLOWED_MIME_TYPES = ['application/pdf'];

    public function validate(string $mimeType): void
    {
        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw InvalidInvoiceFileTypeException::mustBePdf($mimeType);
        }
    }
}
