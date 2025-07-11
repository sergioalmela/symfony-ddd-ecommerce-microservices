<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain\Entity;

use App\Shared\Domain\Entity\Uuid;
use App\Shared\Domain\Exception\InvalidUuidError;
use PHPUnit\Framework\TestCase;

/**
 * Concrete implementation for testing the abstract Uuid class
 */
final readonly class TestUuid extends Uuid
{
    // This class only exists for testing the abstract Uuid behavior
}

final class UuidTest extends TestCase
{
    private const string VALID_UUID_V4 = '550e8400-e29b-41d4-a716-446655440000';
    private const string ANOTHER_VALID_UUID = '6ba7b810-9dad-41d1-80b4-00c04fd430c8';

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldCreateUuidFromValidString(): void
    {
        // When
        $uuid = TestUuid::of(self::VALID_UUID_V4);

        // Then
        $this->assertEquals(self::VALID_UUID_V4, $uuid->value());
        $this->assertEquals(self::VALID_UUID_V4, (string) $uuid);
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldGenerateValidUuid(): void
    {
        // When
        $uuid = TestUuid::generate();

        // Then
        $this->assertNotEmpty($uuid->value());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid->value()
        );
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldGenerateUniqueUuids(): void
    {
        // When
        $uuid1 = TestUuid::generate();
        $uuid2 = TestUuid::generate();

        // Then
        $this->assertNotEquals($uuid1->value(), $uuid2->value());
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldReturnTrueWhenComparingEqualUuids(): void
    {
        // Given
        $uuid1 = TestUuid::of(self::VALID_UUID_V4);
        $uuid2 = TestUuid::of(self::VALID_UUID_V4);

        // When & Then
        $this->assertTrue($uuid1->equals($uuid2));
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldReturnFalseWhenComparingDifferentUuids(): void
    {
        // Given
        $uuid1 = TestUuid::of(self::VALID_UUID_V4);
        $uuid2 = TestUuid::of(self::ANOTHER_VALID_UUID);

        // When & Then
        $this->assertFalse($uuid1->equals($uuid2));
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldThrowInvalidUuidErrorForEmptyString(): void
    {
        $this->expectException(InvalidUuidError::class);
        TestUuid::of('');
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldThrowInvalidUuidErrorForInvalidFormat(): void
    {
        $this->expectException(InvalidUuidError::class);
        TestUuid::of('not-a-uuid');
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldThrowInvalidUuidErrorForWrongLength(): void
    {
        $this->expectException(InvalidUuidError::class);
        TestUuid::of('550e8400-e29b-41d4-a716-44665544000');
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldThrowInvalidUuidErrorForInvalidCharacters(): void
    {
        $this->expectException(InvalidUuidError::class);
        TestUuid::of('550e8400-e29b-41d4-a716-44665544000g');
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldThrowInvalidUuidErrorForMissingDashes(): void
    {
        $this->expectException(InvalidUuidError::class);
        TestUuid::of('550e8400e29b41d4a716446655440000');
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldThrowInvalidUuidErrorForWrongVersion(): void
    {
        // UUID v1 (not v4)
        $this->expectException(InvalidUuidError::class);
        TestUuid::of('550e8400-e29b-11d4-a716-446655440000');
    }

    /**
     * @group value-objects
     * @group uuid-validation
     */
    public function testShouldCreateUuidFromPrimitives(): void
    {
        // When
        $uuid = TestUuid::fromPrimitives(self::VALID_UUID_V4);

        // Then
        $this->assertEquals(self::VALID_UUID_V4, $uuid->value());
    }

    /**
     * @group value-objects
     * @group immutability
     */
    public function testShouldBeImmutable(): void
    {
        // Given
        $originalValue = self::VALID_UUID_V4;
        $uuid = TestUuid::of($originalValue);

        // When
        $retrievedValue = $uuid->value();

        // Then
        $this->assertEquals($originalValue, $retrievedValue);
        $this->assertEquals($originalValue, (string) $uuid);
    }
}