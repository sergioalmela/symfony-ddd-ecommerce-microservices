<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Application\Command\RecordOrderShipment;

use App\Invoice\Application\Command\RecordOrderShipment\RecordOrderShipmentCommand;
use App\Invoice\Application\Command\RecordOrderShipment\RecordOrderShipmentCommandHandler;
use App\Shared\Domain\Exception\InvalidUuidError;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use App\Tests\Invoice\Infrastructure\Testing\Builders\OrderProjectionBuilder;
use App\Tests\Invoice\Infrastructure\Testing\Doubles\OrderProjectionRepositorySpy;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecordOrderShipmentCommandHandlerTest extends TestCase
{
    private OrderProjectionRepositorySpy $orderProjectionRepository;
    private RecordOrderShipmentCommandHandler $handler;

    protected function setUp(): void
    {
        $this->orderProjectionRepository = new OrderProjectionRepositorySpy();
        $this->handler = new RecordOrderShipmentCommandHandler($this->orderProjectionRepository);
    }

    public function testShouldCreateShippedProjectionWhenShipmentArrivesBeforeCreation(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();
        $shippedAt = new DateTimeImmutable('-1 hour');

        ($this->handler)(new RecordOrderShipmentCommand($orderId->value(), $sellerId->value(), $shippedAt));

        $this->assertCount(1, $this->orderProjectionRepository->storedProjections());
        $storedProjection = $this->orderProjectionRepository->storedProjections()[0];
        $this->assertTrue($storedProjection->orderId()->equals($orderId));
        $this->assertTrue($storedProjection->sellerId()->equals($sellerId));
        $this->assertTrue($storedProjection->isShipped());
        $this->assertSame($shippedAt, $storedProjection->shippedAt());
    }

    public function testShouldMarkExistingProjectionAsShipped(): void
    {
        $orderProjection = OrderProjectionBuilder::anOrderProjection()->build();
        $this->orderProjectionRepository->add($orderProjection);

        ($this->handler)(new RecordOrderShipmentCommand(
            $orderProjection->orderId()->value(),
            $orderProjection->sellerId()->value(),
            new DateTimeImmutable()
        ));

        $this->assertSame([$orderProjection], $this->orderProjectionRepository->storedProjections());
        $this->assertTrue($orderProjection->isShipped());
    }

    public function testShouldKeepFirstShipmentTimeOnRepeatedDelivery(): void
    {
        $firstShippedAt = new DateTimeImmutable('-2 hours');
        $orderProjection = OrderProjectionBuilder::anOrderProjection()->withShippedAt($firstShippedAt)->build();
        $this->orderProjectionRepository->add($orderProjection);

        ($this->handler)(new RecordOrderShipmentCommand(
            $orderProjection->orderId()->value(),
            $orderProjection->sellerId()->value(),
            new DateTimeImmutable()
        ));

        $this->assertSame($firstShippedAt, $orderProjection->shippedAt());
    }

    #[DataProvider('invalidIds')]
    public function testShouldRejectInvalidIdsWithoutSaving(string $orderId, string $sellerId): void
    {
        try {
            ($this->handler)(new RecordOrderShipmentCommand($orderId, $sellerId, new DateTimeImmutable()));
            $this->fail('Expected invalid identifier to be rejected');
        } catch (InvalidUuidError) {
            $this->assertFalse($this->orderProjectionRepository->storeChanged());
        }
    }

    public static function invalidIds(): iterable
    {
        yield 'invalid order id' => ['not-a-uuid', SellerId::generate()->value()];
        yield 'invalid seller id' => [OrderId::generate()->value(), 'not-a-uuid'];
    }
}
