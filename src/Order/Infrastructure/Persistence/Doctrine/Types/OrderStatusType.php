<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use App\Order\Domain\ValueObject\OrderStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Override;

final class OrderStatusType extends StringType
{
    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof OrderStatus ? $value->value() : $value;
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?OrderStatus
    {
        return \is_string($value) ? OrderStatus::fromPrimitives($value) : null;
    }

    #[Override]
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
