<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class OrderStatusTransitionInvalidException extends DomainException
{
    public function __construct(string $current, string $next)
    {
        parent::__construct(\sprintf('Cannot change order status from %s to %s', $current, $next));
    }
}
