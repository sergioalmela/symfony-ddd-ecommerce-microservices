<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use App\Order\Domain\ValueObject\Price;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DecimalType;
use Override;

final class PriceType extends DecimalType
{
    public const string NAME = 'price';

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof Price ? number_format($value->value(), 2, '.', '') : $value;
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

    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['precision'] ??= 10;
        $column['scale'] ??= 2;

        return parent::getSQLDeclaration($column, $platform);
    }
}
