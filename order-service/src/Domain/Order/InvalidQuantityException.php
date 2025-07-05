<?php

declare(strict_types=1);

namespace OrderService\Domain\Order;

use InvalidArgumentException;

final class InvalidQuantityException extends InvalidArgumentException
{
    public function __construct(string $message = 'Invalid quantity provided')
    {
        parent::__construct($message);
    }
}
