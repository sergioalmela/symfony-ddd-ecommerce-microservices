<?php

declare(strict_types=1);

namespace App\Order\Application\Command\UpdateOrderStatus;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\Exception\OrderNotFoundException;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final readonly class UpdateOrderStatusCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(UpdateOrderStatusCommand $updateOrderStatusCommand): void
    {
        $orderId = OrderId::of($updateOrderStatusCommand->id);
        $sellerId = SellerId::of($updateOrderStatusCommand->sellerId);
        $orderStatus = OrderStatus::of($updateOrderStatusCommand->status);

        $order = $this->orderRepository->findByIdAndSeller($orderId, $sellerId);

        if (!$order instanceof Order) {
            throw new OrderNotFoundException($updateOrderStatusCommand->id);
        }

        $order->updateStatus(
            $orderStatus,
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
