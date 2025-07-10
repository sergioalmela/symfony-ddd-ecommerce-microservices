<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvoiceNotFoundException extends DomainException
{
    public function __construct(string $orderId)
    {
        parent::__construct("Invoice with order ID: {$orderId} not found.");
    }
}
