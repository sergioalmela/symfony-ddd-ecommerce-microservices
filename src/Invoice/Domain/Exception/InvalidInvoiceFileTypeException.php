<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidInvoiceFileTypeException extends DomainException
{
    public static function mustBePdf(string $actualMimeType): self
    {
        return new self(\sprintf('Invoice files must be PDF. Received: %s', $actualMimeType));
    }
}
