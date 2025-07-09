<?php

declare(strict_types=1);

namespace App\Invoice\Domain\ValueObject;

use Stringable;
use DateTimeImmutable;
use Exception;
use App\Invoice\Domain\Exception\InvalidSentAtException;

final readonly class SentAt implements Stringable
{
    private function __construct(
        private DateTimeImmutable $value,
    ) {
    }

    public static function of(DateTimeImmutable $value): self
    {
        $now = new DateTimeImmutable();

        // Don't allow future dates
        if ($value > $now) {
            throw new InvalidSentAtException('SentAt date cannot be in the future');
        }

        // Don't allow dates too far in the past (1 year)
        $oneYearAgo = $now->modify('-1 year');
        if ($value < $oneYearAgo) {
            throw new InvalidSentAtException('SentAt date cannot be more than 1 year ago');
        }

        return new self($value);
    }

    public static function now(): self
    {
        return new self(new DateTimeImmutable());
    }

    public static function fromString(string $dateString): self
    {
        try {
            $date = new DateTimeImmutable($dateString);

            return self::of($date);
        } catch (Exception) {
            throw new InvalidSentAtException("Invalid date format: {$dateString}");
        }
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

    public function isToday(): bool
    {
        $today = new DateTimeImmutable('today');

        return $this->value >= $today && $this->value < $today->modify('+1 day');
    }

    public function daysSinceNow(): int
    {
        $now = new DateTimeImmutable();

        return $now->diff($this->value)->days;
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
