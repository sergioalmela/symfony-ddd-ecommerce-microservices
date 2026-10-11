<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Doctrine\Types;

use App\Invoice\Domain\ValueObject\SentAt;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Override;

final class SentAtType extends Type
{
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTypeDeclarationSQL($column);
    }

    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof SentAt) {
            $value = $value->value();
        }

        return Type::getType(Types::DATETIME_IMMUTABLE)->convertToDatabaseValue($value, $platform);
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?SentAt
    {
        $dateTime = Type::getType(Types::DATETIME_IMMUTABLE)->convertToPHPValue($value, $platform);

        return $dateTime instanceof DateTimeImmutable ? SentAt::fromPrimitives($dateTime) : null;
    }
}
