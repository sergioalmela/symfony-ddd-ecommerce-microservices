<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use App\Order\Domain\ValueObject\Quantity;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;

final class QuantityType extends IntegerType
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        return $value instanceof Quantity ? $value->value() : $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Quantity
    {
        return is_numeric($value) ? Quantity::fromPrimitives((int) $value) : null;
    }
}
