<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use App\Shared\Domain\ValueObject\SellerId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Override;

final class SellerIdType extends GuidType
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
    public function convertToPHPValue($value, AbstractPlatform $platform): ?SellerId
    {
        if (null === $value) {
            return null;
        }

        return SellerId::of($value);
    }
}
