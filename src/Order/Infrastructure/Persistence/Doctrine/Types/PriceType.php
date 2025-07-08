<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use App\Order\Domain\ValueObject\Price;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DecimalType;

final class PriceType extends DecimalType
{
    public const NAME = 'price';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?float
    {
        return $value instanceof Price ? $value->value() : $value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Price
    {
        return is_numeric($value) ? Price::fromPrimitives((float) $value) : null;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
