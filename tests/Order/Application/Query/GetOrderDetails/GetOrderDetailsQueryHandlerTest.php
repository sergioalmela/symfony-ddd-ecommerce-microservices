<?php

declare(strict_types=1);

namespace App\Tests\Order\Application\Query\GetOrderDetails;

use App\Order\Application\Query\GetOrderDetails\GetOrderDetailsQuery;
use App\Order\Application\Query\GetOrderDetails\GetOrderDetailsQueryHandler;
use App\Order\Application\Query\GetOrderDetails\GetOrderDetailsResponse;
use App\Shared\Domain\Exception\InvalidUuidError;
use App\Shared\Domain\Exception\OrderNotFoundException;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use PHPUnit\Framework\TestCase;
use App\Tests\Order\Infrastructure\Testing\Builders\OrderBuilder;
use App\Tests\Order\Infrastructure\Testing\Doubles\OrderRepositoryFake;

final class GetOrderDetailsQueryHandlerTest extends TestCase
{
    private OrderRepositoryFake $orderRepository;
    private GetOrderDetailsQueryHandler $handler;

    private OrderId $validOrderId;
    private SellerId $validSellerId;
    private SellerId $externalSellerId;
    private string $invalidId;

    protected function setUp(): void
    {
        $this->orderRepository = new OrderRepositoryFake();
        $this->handler = new GetOrderDetailsQueryHandler($this->orderRepository);

        $this->validOrderId = OrderId::generate();
        $this->validSellerId = SellerId::generate();
        $this->externalSellerId = SellerId::generate();
        $this->invalidId = 'invalid-id';
    }

    protected function tearDown(): void
    {
        $this->orderRepository->clean();
    }

    public function testShouldThrowInvalidUuidExceptionForInvalidOrderId(): void
    {
        $query = new GetOrderDetailsQuery(
            $this->invalidId,
            $this->validSellerId->value()
        );

        $this->expectException(InvalidUuidError::class);

        ($this->handler)($query);
    }

    public function testShouldThrowInvalidUuidExceptionForInvalidSellerId(): void
    {
        $query = new GetOrderDetailsQuery(
            $this->validOrderId->value(),
            $this->invalidId
        );

        $this->expectException(InvalidUuidError::class);

        ($this->handler)($query);
    }

    public function testShouldThrowOrderNotFoundExceptionWhenOrderDoesNotExist(): void
    {
        $query = new GetOrderDetailsQuery(
            $this->validOrderId->value(),
            $this->validSellerId->value()
        );

        $this->expectException(OrderNotFoundException::class);

        ($this->handler)($query);
    }

    public function testShouldThrowOrderNotFoundExceptionWhenOrderIsAccessedByExternalSeller(): void
    {
        $order = OrderBuilder::anOrder()
            ->withId($this->validOrderId)
            ->withSellerId($this->validSellerId)
            ->build();
        $this->orderRepository->add($order);

        $query = new GetOrderDetailsQuery(
            $this->validOrderId->value(),
            $this->externalSellerId->value()
        );

        $this->expectException(OrderNotFoundException::class);

        ($this->handler)($query);
    }

    public function testShouldReturnOrderDetailsWhenOrderExistsAndSellerIsValid(): void
    {
        $order = OrderBuilder::anOrder()
            ->withId($this->validOrderId)
            ->withSellerId($this->validSellerId)
            ->build();
        $this->orderRepository->add($order);

        $query = new GetOrderDetailsQuery(
            $this->validOrderId->value(),
            $this->validSellerId->value()
        );

        $result = ($this->handler)($query);

        $this->assertInstanceOf(GetOrderDetailsResponse::class, $result);
        $this->assertEquals($order->toPrimitives(), $result->order->toPrimitives());
    }
}