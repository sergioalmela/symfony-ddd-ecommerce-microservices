<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use App\Order\Domain\Exception\InvalidQuantityException;
use Stringable;

final readonly class Quantity implements Stringable
{
    private function __construct(
        private int $value,
    ) {
    }

    public static function of(int $value): self
    {
        if ($value < 0) {
            throw new InvalidQuantityException('Quantity cannot be negative');
        }

        if ($value > 999) {
            throw new InvalidQuantityException('Quantity cannot exceed 999 items');
        }

        return new self($value);
    }

    public static function fromPrimitives(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
