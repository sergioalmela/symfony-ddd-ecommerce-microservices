<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Order\Domain\ValueObject\OrderStatusType;
use App\Shared\Domain\Exception\DomainException;

final class OrderStatusInvalidException extends DomainException
{
    public function __construct(string $value)
    {
        $validStatuses = implode(', ', array_column(OrderStatusType::cases(), 'value'));

        parent::__construct(
            \sprintf(
                'Invalid order status "%s". Valid statuses are: %s',
                $value,
                $validStatuses
            )
        );
    }
}
