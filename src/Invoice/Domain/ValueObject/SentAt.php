<?php

declare(strict_types=1);

namespace App\Invoice\Domain\ValueObject;

use App\Invoice\Domain\Exception\InvalidSentAtException;
use DateTimeImmutable;
use Stringable;

final readonly class SentAt implements Stringable
{
    private function __construct(
        private DateTimeImmutable $value,
    ) {
    }

    public static function of(DateTimeImmutable $value): self
    {
        $now = new DateTimeImmutable();

        if ($value > $now) {
            throw new InvalidSentAtException('SentAt date cannot be in the future');
        }

        $oneYearAgo = $now->modify('-1 year');
        if ($value < $oneYearAgo) {
            throw new InvalidSentAtException('SentAt date cannot be more than 1 year ago');
        }

        return new self($value);
    }

    public static function fromPrimitives(DateTimeImmutable $value): self
    {
        return new self($value);
    }

    public function value(): DateTimeImmutable
    {
        return $this->value;
    }

    public function format(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->value->format($format);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value->format('Y-m-d H:i:s');
    }
}
