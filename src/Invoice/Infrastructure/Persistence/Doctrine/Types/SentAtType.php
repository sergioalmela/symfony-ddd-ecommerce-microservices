<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Doctrine\Types;

use App\Invoice\Domain\ValueObject\SentAt;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;
use Override;

final class SentAtType extends DateTimeType
{
    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof SentAt) {
            return parent::convertToDatabaseValue($value->value(), $platform);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?SentAt
    {
        $dateTime = parent::convertToPHPValue($value, $platform);

        return $dateTime ? SentAt::fromPrimitives(DateTimeImmutable::createFromInterface($dateTime)) : null;
    }

    #[Override]
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
