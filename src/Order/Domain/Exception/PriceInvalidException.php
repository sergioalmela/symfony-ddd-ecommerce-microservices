<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

class PriceInvalidException extends DomainException
{
    public function __construct(float $price)
    {
        parent::__construct("Invalid price: {$price}");
    }
}
