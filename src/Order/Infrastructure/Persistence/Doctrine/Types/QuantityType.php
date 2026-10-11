<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use App\Order\Domain\ValueObject\Quantity;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;

final class QuantityType extends Type
{
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    #[Override]
    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        return $value instanceof Quantity ? $value->value() : $value;
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Quantity
    {
        return is_numeric($value) ? Quantity::fromPrimitives((int) $value) : null;
    }
}
