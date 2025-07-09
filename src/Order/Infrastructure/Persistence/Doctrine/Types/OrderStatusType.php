<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use App\Order\Domain\ValueObject\OrderStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class OrderStatusType extends StringType
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof OrderStatus ? $value->value() : $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?OrderStatus
    {
        // If the value from the DB is a string, use your static factory method to reconstruct the object.
        return \is_string($value) ? OrderStatus::fromPrimitives($value) : null;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
