<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use InvalidArgumentException;
use App\Order\Domain\ValueObject\OrderStatusType;

final class OrderStatusInvalidException extends InvalidArgumentException
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
