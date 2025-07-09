<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Doctrine\Types;

use Override;
use App\Shared\Domain\ValueObject\ProductId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

final class ProductIdType extends GuidType
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
    public function convertToPHPValue($value, AbstractPlatform $platform): ?ProductId
    {
        if (null === $value) {
            return null;
        }

        return ProductId::of($value);
    }
}
