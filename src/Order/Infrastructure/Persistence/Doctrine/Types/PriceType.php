<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use Override;
use App\Order\Domain\ValueObject\Price;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DecimalType;

final class PriceType extends DecimalType
{
    public const string NAME = 'price';

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?float
    {
        return $value instanceof Price ? $value->value() : $value;
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Price
    {
        return is_numeric($value) ? Price::fromPrimitives((float) $value) : null;
    }

    #[Override]
    public function getName(): string
    {
        return self::NAME;
    }
}
