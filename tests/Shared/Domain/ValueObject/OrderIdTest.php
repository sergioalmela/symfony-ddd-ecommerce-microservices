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
        $orderId = OrderId::of(self::VALID_UUID);

        $this->assertInstanceOf(OrderId::class, $orderId);
        $this->assertSame(self::VALID_UUID, $orderId->value());
    }

    /**
     * @group value-objects
     * @group id-specific
     */
    public function testShouldGenerateOrderIdInstance(): void
    {
        $orderId = OrderId::generate();

        $this->assertInstanceOf(OrderId::class, $orderId);
        $this->assertNotEmpty($orderId->value());
    }

    /**
     * @group value-objects
     * @group id-specific
     */
    public function testShouldCreateFromPrimitives(): void
    {
        $orderId = OrderId::fromPrimitives(self::VALID_UUID);

        $this->assertInstanceOf(OrderId::class, $orderId);
        $this->assertSame(self::VALID_UUID, $orderId->value());
    }

    /**
     * @group value-objects
     * @group type-safety
     */
    public function testShouldBeDistinctFromOtherIdTypes(): void
    {
        $orderId = OrderId::of(self::VALID_UUID);

        $this->assertInstanceOf(OrderId::class, $orderId);
    }

    /**
     * @group value-objects
     * @group equality
     */
    public function testShouldCompareOrderIdsCorrectly(): void
    {
        $orderId1 = OrderId::of(self::VALID_UUID);
        $orderId2 = OrderId::of(self::VALID_UUID);
        $orderId3 = OrderId::generate();

        $this->assertTrue($orderId1->equals($orderId2));
        $this->assertFalse($orderId1->equals($orderId3));
    }
}
