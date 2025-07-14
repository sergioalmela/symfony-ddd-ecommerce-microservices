<?php

declare(strict_types=1);

namespace App\Order\Domain\ValueObject;

use App\Order\Domain\Exception\PriceInvalidException;
use Stringable;

final readonly class Price implements Stringable
{
    private float $value;

    private function __construct(float $value)
    {
        $this->value = round($value * 100) / 100;
    }

    public static function of(float $value): self
    {
        if (!is_finite($value) || $value < 0) {
            throw new PriceInvalidException($value);
        }

        return new self($value);
    }

    public static function fromPrimitives(float $value): self
    {
        return new self($value);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return number_format($this->value, 2, '.', '');
    }
}
