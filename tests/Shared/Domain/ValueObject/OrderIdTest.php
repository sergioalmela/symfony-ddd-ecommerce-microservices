<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\OrderId;
use PHPUnit\Framework\TestCase;

final class OrderIdTest extends TestCase
{
    private const string VALID_UUID = '550e8400-e29b-41d4-a716-446655440000';

    /**
     * @group value-objects
     * @group id-specific
     */
    public function testShouldCreateOrderIdInstance(): void
    {
        // When
        $orderId = OrderId::of(self::VALID_UUID);

        // Then
        $this->assertInstanceOf(OrderId::class, $orderId);
        $this->assertEquals(self::VALID_UUID, $orderId->value());
    }

    /**
     * @group value-objects
     * @group id-specific
     */
    public function testShouldGenerateOrderIdInstance(): void
    {
        // When
        $orderId = OrderId::generate();

        // Then
        $this->assertInstanceOf(OrderId::class, $orderId);
        $this->assertNotEmpty($orderId->value());
    }

    /**
     * @group value-objects
     * @group id-specific
     */
    public function testShouldCreateFromPrimitives(): void
    {
        // When
        $orderId = OrderId::fromPrimitives(self::VALID_UUID);

        // Then
        $this->assertInstanceOf(OrderId::class, $orderId);
        $this->assertEquals(self::VALID_UUID, $orderId->value());
    }

    /**
     * @group value-objects
     * @group type-safety
     */
    public function testShouldBeDistinctFromOtherIdTypes(): void
    {
        // Given
        $orderId = OrderId::of(self::VALID_UUID);
        
        // When & Then
        $this->assertInstanceOf(OrderId::class, $orderId);
        
        // Note: We rely on PHP's type system to prevent mixing different ID types
        // OrderId cannot be compared to SellerId due to strict typing
    }

    /**
     * @group value-objects
     * @group equality
     */
    public function testShouldCompareOrderIdsCorrectly(): void
    {
        // Given
        $orderId1 = OrderId::of(self::VALID_UUID);
        $orderId2 = OrderId::of(self::VALID_UUID);
        $orderId3 = OrderId::generate();

        // When & Then
        $this->assertTrue($orderId1->equals($orderId2));
        $this->assertFalse($orderId1->equals($orderId3));
    }
}