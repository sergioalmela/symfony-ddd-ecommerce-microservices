<?php

declare(strict_types=1);

namespace App\Tests\Order\Application\Query\GetOrders;

use App\Order\Application\Query\GetOrders\GetOrdersQuery;
use App\Order\Application\Query\GetOrders\GetOrdersQueryHandler;
use App\Order\Application\Query\GetOrders\GetOrdersResponse;
use App\Shared\Domain\Exception\InvalidUuidError;
use App\Shared\Domain\ValueObject\SellerId;
use PHPUnit\Framework\TestCase;
use App\Tests\Order\Infrastructure\Testing\Builders\OrderBuilder;
use App\Tests\Order\Infrastructure\Testing\Doubles\OrderRepositoryFake;

final class GetOrdersQueryHandlerTest extends TestCase
{
    private OrderRepositoryFake $orderRepository;
    private GetOrdersQueryHandler $handler;

    private SellerId $validSellerId;
    private string $invalidSellerId;

    protected function setUp(): void
    {
        $this->orderRepository = new OrderRepositoryFake();
        $this->handler = new GetOrdersQueryHandler($this->orderRepository);

        $this->validSellerId = SellerId::generate();
        $this->invalidSellerId = 'invalid-seller-id';
    }

    protected function tearDown(): void
    {
        $this->orderRepository->clean();
    }

    public function testShouldThrowInvalidUuidExceptionForInvalidSellerId(): void
    {
        $query = new GetOrdersQuery($this->invalidSellerId);

        $this->expectException(InvalidUuidError::class);

        ($this->handler)($query);
    }

    public function testShouldReturnEmptyResultWhenNoOrders(): void
    {
        $query = new GetOrdersQuery($this->validSellerId->value());

        $result = ($this->handler)($query);

        $this->assertInstanceOf(GetOrdersResponse::class, $result);
        $this->assertEmpty($result->orders);
    }

    public function testShouldReturnListOfOrdersWhenOrdersExist(): void
    {
        $orderOne = OrderBuilder::anOrder()
            ->withSellerId($this->validSellerId)
            ->build();
        $orderTwo = OrderBuilder::anOrder()
            ->withSellerId($this->validSellerId)
            ->build();
        
        $this->orderRepository->add($orderOne);
        $this->orderRepository->add($orderTwo);

        $query = new GetOrdersQuery($this->validSellerId->value());

        $result = ($this->handler)($query);

        $this->assertInstanceOf(GetOrdersResponse::class, $result);
        $this->assertCount(2, $result->orders);
        $this->assertEquals($orderOne->toPrimitives(), $result->orders[0]->toPrimitives());
        $this->assertEquals($orderTwo->toPrimitives(), $result->orders[1]->toPrimitives());
    }

    public function testShouldReturnOnlyOrdersForSpecificSeller(): void
    {
        $otherSellerId = SellerId::generate();
        
        $orderForSeller = OrderBuilder::anOrder()
            ->withSellerId($this->validSellerId)
            ->build();
        $orderForOtherSeller = OrderBuilder::anOrder()
            ->withSellerId($otherSellerId)
            ->build();
        
        $this->orderRepository->add($orderForSeller);
        $this->orderRepository->add($orderForOtherSeller);

        $query = new GetOrdersQuery($this->validSellerId->value());

        $result = ($this->handler)($query);

        $this->assertInstanceOf(GetOrdersResponse::class, $result);
        $this->assertCount(1, $result->orders);
        $this->assertEquals($orderForSeller->toPrimitives(), $result->orders[0]->toPrimitives());
    }
}