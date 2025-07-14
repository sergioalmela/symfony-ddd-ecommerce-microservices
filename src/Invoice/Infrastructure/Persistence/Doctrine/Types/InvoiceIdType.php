<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Doctrine\Types;

use App\Invoice\Domain\ValueObject\InvoiceId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Override;

final class InvoiceIdType extends GuidType
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
    public function convertToPHPValue($value, AbstractPlatform $platform): ?InvoiceId
    {
        if (null === $value) {
            return null;
        }

        return InvoiceId::of($value);
    }
}
