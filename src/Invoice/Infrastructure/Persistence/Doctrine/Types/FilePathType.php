<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Doctrine\Types;

use App\Invoice\Domain\ValueObject\FilePath;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Override;

final class FilePathType extends StringType
{
    #[Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof FilePath ? $value->value() : $value;
    }

    #[Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?FilePath
    {
        return \is_string($value) ? FilePath::fromPrimitives($value) : null;
    }

    #[Override]
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
