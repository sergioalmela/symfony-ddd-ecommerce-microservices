<?php

declare(strict_types=1);

namespace App\Order\Application\Command\CreateOrder;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\OrderAlreadyExistsException;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\SellerId;

final readonly class CreateOrderCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(CreateOrderCommand $createOrderCommand): void
    {
        $orderId = OrderId::of($createOrderCommand->id);

        if ($this->orderRepository->find($orderId) instanceof Order) {
            throw new OrderAlreadyExistsException($orderId->value());
        }

        $order = Order::create(
            $orderId,
            ProductId::of($createOrderCommand->productId),
            Quantity::of($createOrderCommand->quantity),
            Price::of($createOrderCommand->price),
            CustomerId::of($createOrderCommand->customerId),
            SellerId::of($createOrderCommand->sellerId)
        );

        $this->orderRepository->save($order);

        $this->publishDomainEvents($order);
    }

    private function publishDomainEvents(Order $order): void
    {
        $events = $order->releaseEvents();

        foreach ($events as $event) {
            $this->eventBus->publish($event);
        }
    }
}
