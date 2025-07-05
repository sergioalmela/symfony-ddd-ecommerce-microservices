<?php

declare(strict_types=1);

namespace OrderService\Domain\Order;

use Shared\Domain\Exceptions\DomainException;

class PriceInvalidException extends DomainException
{
    public function __construct(float $price)
    {
        parent::__construct("Invalid price: {$price}");
    }
}
