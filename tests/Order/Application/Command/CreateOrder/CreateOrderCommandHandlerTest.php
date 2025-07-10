<?php

declare(strict_types=1);

namespace App\Tests\Order\Application\Command\CreateOrder;

use App\Order\Application\Command\CreateOrder\CreateOrderCommand;
use App\Order\Application\Command\CreateOrder\CreateOrderCommandHandler;
use App\Order\Domain\Event\OrderCreatedEvent;
use App\Order\Domain\Exception\OrderAlreadyExistsException;
use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Order\Domain\Exception\InvalidQuantityException;
use App\Order\Domain\Exception\PriceInvalidException;
use App\Shared\Domain\Exception\InvalidUuidError;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\SellerId;
use PHPUnit\Framework\TestCase;
use App\Tests\Order\Infrastructure\Testing\Builders\OrderBuilder;
use App\Tests\Order\Infrastructure\Testing\Doubles\EventBusSpy;
use App\Tests\Order\Infrastructure\Testing\Doubles\OrderRepositoryFake;

final class CreateOrderCommandHandlerTest extends TestCase
{
    private OrderRepositoryFake $orderRepository;
    private EventBusSpy $eventBus;
    private CreateOrderCommandHandler $handler;

    private OrderId $validId;
    private string $validIdString;
    private ProductId $validProductId;
    private Quantity $validQuantity;
    private Price $validPrice;
    private CustomerId $validCustomerId;
    private SellerId $validSellerId;
    private string $invalidId;
    private float $invalidPrice;
    private int $invalidQuantity;

    protected function setUp(): void
    {
        $this->orderRepository = new OrderRepositoryFake();
        $this->eventBus = new EventBusSpy();
        $this->handler = new CreateOrderCommandHandler($this->orderRepository, $this->eventBus);

        $this->validId = OrderId::generate();
        $this->validIdString = $this->validId->value();
        $this->validProductId = ProductId::generate();
        $this->validQuantity = Quantity::of(1);
        $this->validPrice = Price::of(10.50);
        $this->validCustomerId = CustomerId::generate();
        $this->validSellerId = SellerId::generate();
        $this->invalidId = 'invalid-id';
        $this->invalidPrice = -1.0;
        $this->invalidQuantity = -1;
    }

    protected function tearDown(): void
    {
        $this->orderRepository->clean();
        $this->eventBus->clean();
    }

    public function testShouldThrowOrderAlreadyExistsExceptionWhenOrderExists(): void
    {
        $existingOrder = OrderBuilder::anOrder()->withId($this->validId)->build();
        $this->orderRepository->add($existingOrder);

        $command = new CreateOrderCommand(
            $this->validIdString,
            $this->validProductId->value(),
            $this->validQuantity->value(),
            $this->validPrice->value(),
            $this->validCustomerId->value(),
            $this->validSellerId->value()
        );

        $this->expectException(OrderAlreadyExistsException::class);

        ($this->handler)($command);

        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    public function testShouldCreateOrderWhenOrderIsNew(): void
    {
        $command = new CreateOrderCommand(
            $this->validIdString,
            $this->validProductId->value(),
            $this->validQuantity->value(),
            $this->validPrice->value(),
            $this->validCustomerId->value(),
            $this->validSellerId->value()
        );

        ($this->handler)($command);

        $this->assertTrue($this->orderRepository->storeChanged());
        $this->assertCount(1, $this->orderRepository->stored());

        $storedOrder = $this->orderRepository->stored()[0];
        $expectedOrder = OrderBuilder::anOrder()
            ->withId($this->validId)
            ->withProductId($this->validProductId)
            ->withQuantity($this->validQuantity)
            ->withPrice($this->validPrice)
            ->withCustomerId($this->validCustomerId)
            ->withSellerId($this->validSellerId)
            ->build();

        $this->assertEquals($expectedOrder->toPrimitives(), $storedOrder->toPrimitives());
    }

    public function testShouldDispatchOrderCreatedEventWhenOrderIsCreated(): void
    {
        $command = new CreateOrderCommand(
            $this->validIdString,
            $this->validProductId->value(),
            $this->validQuantity->value(),
            $this->validPrice->value(),
            $this->validCustomerId->value(),
            $this->validSellerId->value()
        );

        ($this->handler)($command);

        $this->assertCount(1, $this->eventBus->domainEvents());

        $dispatchedEvent = $this->eventBus->domainEvents()[0];
        $this->assertInstanceOf(OrderCreatedEvent::class, $dispatchedEvent);
        $this->assertEquals($this->validId->value(), $dispatchedEvent->aggregateId());
    }

    public function testShouldThrowInvalidUuidExceptionForInvalidId(): void
    {
        $command = new CreateOrderCommand(
            $this->invalidId,
            $this->validProductId->value(),
            $this->validQuantity->value(),
            $this->validPrice->value(),
            $this->validCustomerId->value(),
            $this->validSellerId->value()
        );

        $this->expectException(InvalidUuidError::class);

        ($this->handler)($command);
    }

    public function testShouldThrowInvalidQuantityExceptionForNegativeQuantity(): void
    {
        $command = new CreateOrderCommand(
            $this->validIdString,
            $this->validProductId->value(),
            $this->invalidQuantity,
            $this->validPrice->value(),
            $this->validCustomerId->value(),
            $this->validSellerId->value()
        );

        $this->expectException(InvalidQuantityException::class);

        ($this->handler)($command);
    }

    public function testShouldThrowInvalidArgumentExceptionForNegativePrice(): void
    {
        $command = new CreateOrderCommand(
            $this->validIdString,
            $this->validProductId->value(),
            $this->validQuantity->value(),
            $this->invalidPrice,
            $this->validCustomerId->value(),
            $this->validSellerId->value()
        );

        $this->expectException(PriceInvalidException::class);

        ($this->handler)($command);
    }
}