<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Service;

use App\Invoice\Domain\Exception\InvalidInvoiceFileContentException;
use App\Invoice\Domain\Exception\InvalidInvoiceFileTypeException;
use finfo;

final readonly class InvoiceFileValidator
{
    private const array ALLOWED_MIME_TYPES = ['application/pdf'];
    private const int MAX_FILE_SIZE = 10485760;

    public function validate(string $mimeType, string $content): void
    {
        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw InvalidInvoiceFileTypeException::mustBePdf($mimeType);
        }

        if ('' === $content || mb_strlen($content, '8bit') > self::MAX_FILE_SIZE) {
            throw new InvalidInvoiceFileContentException('Invoice file must contain data and be at most 10 MiB');
        }

        $actualMimeType = new finfo(\FILEINFO_MIME_TYPE)->buffer($content);
        if ('application/pdf' !== $actualMimeType) {
            throw InvalidInvoiceFileTypeException::mustBePdf((string) $actualMimeType);
        }
    }
}
