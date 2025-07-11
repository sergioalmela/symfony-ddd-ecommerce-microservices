<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Domain\ValueObject;

use App\Invoice\Domain\Exception\InvalidSentAtException;
use App\Invoice\Domain\ValueObject\SentAt;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SentAtTest extends TestCase
{
    private const string VALID_DATE_STRING = '2025-01-26T10:30:00Z';
    private const string FUTURE_DATE_STRING = '2026-12-31T23:59:59Z';
    private const string OLD_DATE_STRING = '2022-01-01T00:00:00Z';

    /**
     * @group value-objects
     * @group date-validation
     */
    public function testShouldCreateSentAtFromValidDate(): void
    {
        // Given
        $date = new DateTimeImmutable(self::VALID_DATE_STRING);

        // When
        $sentAt = SentAt::of($date);

        // Then
        $this->assertEquals($date, $sentAt->value());
        $this->assertEquals('2025-01-26 10:30:00', $sentAt->format());
    }

    /**
     * @group value-objects
     * @group date-validation
     */
    public function testShouldCreateSentAtFromCurrentTime(): void
    {
        // When
        $sentAt = SentAt::now();

        // Then
        $this->assertInstanceOf(DateTimeImmutable::class, $sentAt->value());
        $this->assertTrue($sentAt->isToday());
    }

    /**
     * @group value-objects
     * @group date-validation
     */
    public function testShouldCreateSentAtFromValidDateString(): void
    {
        // When
        $sentAt = SentAt::fromString(self::VALID_DATE_STRING);

        // Then
        $this->assertEquals('2025-01-26 10:30:00', $sentAt->format());
    }

    /**
     * @group value-objects
     * @group date-validation
     */
    public function testShouldThrowExceptionForFutureDate(): void
    {
        // Given
        $futureDate = new DateTimeImmutable(self::FUTURE_DATE_STRING);

        // When & Then
        $this->expectException(InvalidSentAtException::class);
        $this->expectExceptionMessage('SentAt date cannot be in the future');

        SentAt::of($futureDate);
    }

    /**
     * @group value-objects
     * @group date-validation
     */
    public function testShouldThrowExceptionForTooOldDate(): void
    {
        // Given
        $oldDate = new DateTimeImmutable(self::OLD_DATE_STRING);

        // When & Then
        $this->expectException(InvalidSentAtException::class);
        $this->expectExceptionMessage('SentAt date cannot be more than 1 year ago');

        SentAt::of($oldDate);
    }

    /**
     * @group value-objects
     * @group date-validation
     */
    public function testShouldThrowExceptionForInvalidDateString(): void
    {
        $this->expectException(InvalidSentAtException::class);
        SentAt::fromString('not-a-date');
    }

    /**
     * @group value-objects
     * @group date-validation
     */
    public function testShouldThrowExceptionForInvalidDateFormat(): void
    {
        $this->expectException(InvalidSentAtException::class);
        SentAt::fromString('26/01/2025');
    }

    /**
     * @group value-objects
     * @group business-logic
     */
    public function testShouldDetectTodayCorrectly(): void
    {
        // Given
        $today = new DateTimeImmutable('today 10:00:00');
        $sentAt = SentAt::fromPrimitives($today);

        // When & Then
        $this->assertTrue($sentAt->isToday());
    }

    /**
     * @group value-objects
     * @group business-logic
     */
    public function testShouldDetectNotTodayCorrectly(): void
    {
        // Given
        $yesterday = new DateTimeImmutable('yesterday 10:00:00');
        $sentAt = SentAt::fromPrimitives($yesterday);

        // When & Then
        $this->assertFalse($sentAt->isToday());
    }

    /**
     * @group value-objects
     * @group business-logic
     */
    public function testShouldCalculateDaysSinceNowCorrectly(): void
    {
        // Given
        $threeDaysAgo = new DateTimeImmutable('-3 days');
        $sentAt = SentAt::fromPrimitives($threeDaysAgo);

        // When
        $daysSince = $sentAt->daysSinceNow();

        // Then
        $this->assertEquals(3, $daysSince);
    }

    /**
     * @group value-objects
     * @group formatting
     */
    public function testShouldFormatWithCustomFormat(): void
    {
        // Given
        $date = new DateTimeImmutable('2025-01-26T10:30:45Z');
        $sentAt = SentAt::fromPrimitives($date);

        // When & Then
        $this->assertEquals('2025-01-26 10:30:45', $sentAt->format());
        $this->assertEquals('26/01/2025', $sentAt->format('d/m/Y'));
        $this->assertEquals('2025-01-26T10:30:45+00:00', $sentAt->format('c'));
    }

    /**
     * @group value-objects
     * @group equality
     */
    public function testShouldReturnTrueWhenComparingEqualSentAtValues(): void
    {
        // Given
        $date = new DateTimeImmutable(self::VALID_DATE_STRING);
        $sentAt1 = SentAt::fromPrimitives($date);
        $sentAt2 = SentAt::fromPrimitives($date);

        // When & Then
        $this->assertTrue($sentAt1->equals($sentAt2));
    }

    /**
     * @group value-objects
     * @group equality
     */
    public function testShouldReturnFalseWhenComparingDifferentSentAtValues(): void
    {
        // Given
        $date1 = new DateTimeImmutable('2025-01-26T10:30:00Z');
        $date2 = new DateTimeImmutable('2025-01-26T10:31:00Z');
        $sentAt1 = SentAt::fromPrimitives($date1);
        $sentAt2 = SentAt::fromPrimitives($date2);

        // When & Then
        $this->assertFalse($sentAt1->equals($sentAt2));
    }

    /**
     * @group value-objects
     * @group string-conversion
     */
    public function testShouldConvertToStringCorrectly(): void
    {
        // Given
        $date = new DateTimeImmutable('2025-01-26T10:30:45Z');
        $sentAt = SentAt::fromPrimitives($date);

        // When & Then
        $this->assertEquals('2025-01-26 10:30:45', (string) $sentAt);
    }

    /**
     * @group value-objects
     * @group immutability
     */
    public function testShouldBeImmutable(): void
    {
        // Given
        $originalDate = new DateTimeImmutable(self::VALID_DATE_STRING);
        $sentAt = SentAt::fromPrimitives($originalDate);

        // When
        $retrievedDate = $sentAt->value();

        // Then
        $this->assertEquals($originalDate, $retrievedDate);
    }
}