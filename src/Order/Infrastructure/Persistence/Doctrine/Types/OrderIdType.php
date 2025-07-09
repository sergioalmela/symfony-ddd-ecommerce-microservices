<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use Override;
use App\Order\Domain\ValueObject\OrderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

final class OrderIdType extends GuidType
{
    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return (string) $value;
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?OrderId
    {
        if (null === $value) {
            return null;
        }

        return OrderId::of($value);
    }
}
