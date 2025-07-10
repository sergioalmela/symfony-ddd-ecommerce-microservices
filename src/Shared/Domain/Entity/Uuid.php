<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entity;

use App\Shared\Domain\Exception\InvalidUuidError;
use Stringable;

abstract readonly class Uuid implements Stringable
{
    protected function __construct(
        private string $value,
    ) {
    }

    public static function generate(): static
    {
        $data = random_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0F | 0x40);
        $data[8] = \chr(\ord($data[8]) & 0x3F | 0x80);

        return new static(vsprintf('%s%s-%s-%s-%s-%s%s%s', mb_str_split(bin2hex($data), 4)));
    }

    public static function of(string $value): static
    {
        if (!self::isValid($value)) {
            throw new InvalidUuidError($value);
        }

        return new static($value);
    }

    public static function fromPrimitives(string $value): static
    {
        return new static($value);
    }

    private static function isValid(string $value): bool
    {
        return 1 === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
