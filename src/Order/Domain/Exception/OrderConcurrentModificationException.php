<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use Throwable;

final class OrderConcurrentModificationException extends DomainException
{
    public function __construct(string $orderId, ?Throwable $throwable = null)
    {
        parent::__construct(\sprintf('Order %s was changed by another request. Reload it and try again.', $orderId), 0, $throwable);
    }
}
