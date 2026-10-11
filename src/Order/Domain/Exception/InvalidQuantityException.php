<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidQuantityException extends DomainException
{
    public function __construct(string $message = 'Invalid quantity provided')
    {
        parent::__construct($message);
    }
}
