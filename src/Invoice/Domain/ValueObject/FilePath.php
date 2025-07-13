<?php

declare(strict_types=1);

namespace App\Invoice\Domain\ValueObject;

use App\Invoice\Domain\Exception\InvalidFilePathException;
use Stringable;

final readonly class FilePath implements Stringable
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function of(string $value): self
    {
        $trimmedValue = mb_trim($value);

        if ('' === $trimmedValue || '0' === $trimmedValue) {
            throw new InvalidFilePathException('File path cannot be empty');
        }

        if (mb_strlen($trimmedValue) > 500) {
            throw new InvalidFilePathException('File path cannot exceed 500 characters');
        }

        if (str_contains($trimmedValue, '..')) {
            throw new InvalidFilePathException('File path cannot contain ".." for security reasons');
        }

        return new self($trimmedValue);
    }

    public static function fromPrimitives(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function extension(): string
    {
        return mb_strtolower(pathinfo($this->value, \PATHINFO_EXTENSION));
    }

    public function filename(): string
    {
        return pathinfo($this->value, \PATHINFO_BASENAME);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
