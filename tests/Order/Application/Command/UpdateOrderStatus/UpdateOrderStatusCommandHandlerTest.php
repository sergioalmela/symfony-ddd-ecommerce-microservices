<?php

declare(strict_types=1);

namespace App\Tests\Order\Application\Command\UpdateOrderStatus;

use App\Order\Application\Command\UpdateOrderStatus\UpdateOrderStatusCommand;
use App\Order\Application\Command\UpdateOrderStatus\UpdateOrderStatusCommandHandler;
use App\Order\Domain\Exception\OrderStatusInvalidException;
use App\Shared\Domain\Event\OrderShippedEvent;
use App\Shared\Domain\Exception\InvalidUuidError;
use App\Shared\Domain\Exception\OrderNotFoundException;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use App\Tests\Order\Infrastructure\Testing\Builders\OrderBuilder;
use App\Tests\Order\Infrastructure\Testing\Doubles\EventBusSpy;
use App\Tests\Order\Infrastructure\Testing\Doubles\OrderRepositoryFake;
use PHPUnit\Framework\TestCase;

final class UpdateOrderStatusCommandHandlerTest extends TestCase
{
    private OrderRepositoryFake $orderRepository;
    private EventBusSpy $eventBus;
    private UpdateOrderStatusCommandHandler $handler;

    private OrderId $validOrderId;
    private SellerId $validSellerId;
    private SellerId $externalSellerId;
    private string $validStatus;
    private string $invalidId;
    private string $invalidStatus;

    protected function setUp(): void
    {
        $this->orderRepository = new OrderRepositoryFake();
        $this->eventBus = new EventBusSpy();
        $this->handler = new UpdateOrderStatusCommandHandler($this->orderRepository, $this->eventBus);

        $this->validOrderId = OrderId::generate();
        $this->validSellerId = SellerId::generate();
        $this->externalSellerId = SellerId::generate();
        $this->validStatus = 'SHIPPED';
        $this->invalidId = 'invalid-id';
        $this->invalidStatus = 'invalid-status';
    }

    protected function tearDown(): void
    {
        $this->orderRepository->clean();
        $this->eventBus->clean();
    }

    public function testShouldThrowInvalidUuidExceptionForInvalidOrderId(): void
    {
        $command = new UpdateOrderStatusCommand(
            $this->invalidId,
            $this->validSellerId->value(),
            $this->validStatus
        );

        $this->expectException(InvalidUuidError::class);

        ($this->handler)($command);

        $this->assertFalse($this->orderRepository->storeChanged());
        $this->assertCount(0, $this->orderRepository->stored());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    public function testShouldThrowInvalidUuidExceptionForInvalidSellerId(): void
    {
        $command = new UpdateOrderStatusCommand(
            $this->validOrderId->value(),
            $this->invalidId,
            $this->validStatus
        );

        $this->expectException(InvalidUuidError::class);

        ($this->handler)($command);

        $this->assertFalse($this->orderRepository->storeChanged());
        $this->assertCount(0, $this->orderRepository->stored());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    public function testShouldThrowOrderStatusInvalidExceptionForInvalidStatus(): void
    {
        $command = new UpdateOrderStatusCommand(
            $this->validOrderId->value(),
            $this->validSellerId->value(),
            $this->invalidStatus
        );

        $this->expectException(OrderStatusInvalidException::class);

        ($this->handler)($command);

        $this->assertFalse($this->orderRepository->storeChanged());
        $this->assertCount(0, $this->orderRepository->stored());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    public function testShouldThrowOrderNotFoundExceptionWhenOrderDoesNotExist(): void
    {
        $command = new UpdateOrderStatusCommand(
            $this->validOrderId->value(),
            $this->validSellerId->value(),
            $this->validStatus
        );

        $this->expectException(OrderNotFoundException::class);

        ($this->handler)($command);

        $this->assertFalse($this->orderRepository->storeChanged());
        $this->assertCount(0, $this->orderRepository->stored());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    public function testShouldThrowOrderNotFoundExceptionWhenExternalSellerTriesToUpdate(): void
    {
        $order = OrderBuilder::anOrder()
            ->withId($this->validOrderId)
            ->withSellerId($this->validSellerId)
            ->build();
        $this->orderRepository->add($order);

        $command = new UpdateOrderStatusCommand(
            $this->validOrderId->value(),
            $this->externalSellerId->value(),
            $this->validStatus
        );

        $this->expectException(OrderNotFoundException::class);

        ($this->handler)($command);

        $this->assertFalse($this->orderRepository->storeChanged());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    public function testShouldUpdateOrderStatusWhenSellerTriesToChangeToSameStatus(): void
    {
        $order = OrderBuilder::anOrder()
            ->withId($this->validOrderId)
            ->withSellerId($this->validSellerId)
            ->build();
        $this->orderRepository->add($order);

        $command = new UpdateOrderStatusCommand(
            $this->validOrderId->value(),
            $this->validSellerId->value(),
            'CREATED'
        );

        ($this->handler)($command);

        $this->assertTrue($this->orderRepository->storeChanged());
        $this->assertCount(1, $this->orderRepository->stored());

        $storedOrder = $this->orderRepository->stored()[0];
        $this->assertSame('CREATED', $storedOrder->toPrimitives()['status']);
        $this->assertCount(1, $this->eventBus->domainEvents());
    }

    public function testShouldUpdateOrderStatusAndPublishEventWhenSellerChangesStatus(): void
    {
        $customerId = CustomerId::generate();
        $order = OrderBuilder::anOrder()
            ->withId($this->validOrderId)
            ->withSellerId($this->validSellerId)
            ->withCustomerId($customerId)
            ->build();
        $this->orderRepository->add($order);

        $command = new UpdateOrderStatusCommand(
            $this->validOrderId->value(),
            $this->validSellerId->value(),
            'SHIPPED'
        );

        ($this->handler)($command);

        $this->assertTrue($this->orderRepository->storeChanged());
        $this->assertCount(1, $this->orderRepository->stored());

        $storedOrder = $this->orderRepository->stored()[0];
        $this->assertSame('SHIPPED', $storedOrder->toPrimitives()['status']);

        $this->assertCount(2, $this->eventBus->domainEvents());
        $dispatchedEvent = $this->eventBus->domainEvents()[1];
        $this->assertInstanceOf(OrderShippedEvent::class, $dispatchedEvent);
        $this->assertSame($this->validOrderId->value(), $dispatchedEvent->aggregateId());
        $this->assertSame('order.shipped', $dispatchedEvent->eventType());
        $this->assertSame(1, $dispatchedEvent->eventVersion());
        $this->assertSame([
            'orderId' => $this->validOrderId->value(),
            'customerId' => $customerId->value(),
        ], $dispatchedEvent->payload());
    }
}
