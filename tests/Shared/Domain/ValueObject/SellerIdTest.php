<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\SellerId;
use PHPUnit\Framework\TestCase;

final class SellerIdTest extends TestCase
{
    private const string VALID_UUID = '550e8400-e29b-41d4-a716-446655440000';

    /**
     * @group value-objects
     * @group id-specific
     */
    public function testShouldCreateSellerIdInstance(): void
    {
        // When
        $sellerId = SellerId::of(self::VALID_UUID);

        // Then
        $this->assertInstanceOf(SellerId::class, $sellerId);
        $this->assertEquals(self::VALID_UUID, $sellerId->value());
    }

    /**
     * @group value-objects
     * @group id-specific
     */
    public function testShouldGenerateSellerIdInstance(): void
    {
        // When
        $sellerId = SellerId::generate();

        // Then
        $this->assertInstanceOf(SellerId::class, $sellerId);
        $this->assertNotEmpty($sellerId->value());
    }
}