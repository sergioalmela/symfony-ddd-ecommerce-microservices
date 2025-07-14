<?php

declare(strict_types=1);

namespace App\Invoice\Domain\ValueObject;

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
