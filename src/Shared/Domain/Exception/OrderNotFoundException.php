<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class OrderNotFoundException extends DomainException
{
    public function __construct(string $orderId)
    {
        parent::__construct("Order with ID: {$orderId} not found.");
    }
}
