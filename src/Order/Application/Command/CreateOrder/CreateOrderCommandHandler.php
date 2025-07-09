<?php

declare(strict_types=1);

namespace App\Order\Application\Command\CreateOrder;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\OrderAlreadyExistsException;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\SellerId;

final readonly class CreateOrderCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(CreateOrderCommand $command): void
    {
        $orderId = OrderId::of($command->id);

        if ($this->orderRepository->find($orderId)) {
            throw new OrderAlreadyExistsException($orderId->value());
        }

        $order = Order::create(
            id: $orderId,
            productId: ProductId::of($command->productId),
            quantity: Quantity::of($command->quantity),
            price: Price::of($command->price),
            customerId: CustomerId::of($command->customerId),
            sellerId: SellerId::of($command->sellerId)
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
