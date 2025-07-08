<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class OrderAlreadyExistsException extends DomainException
{
    public function __construct(string $orderId)
    {
        parent::__construct("Order with ID: {$orderId} already exists.");
    }
}
