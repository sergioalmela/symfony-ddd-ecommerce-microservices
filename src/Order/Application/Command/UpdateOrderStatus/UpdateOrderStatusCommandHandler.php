<?php

declare(strict_types=1);

namespace App\Order\Application\Command\UpdateOrderStatus;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\Domain\ValueObject\OrderId;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\ValueObject\SellerId;

final readonly class UpdateOrderStatusCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(UpdateOrderStatusCommand $command): void
    {
        $orderId = OrderId::of($command->id);
        $sellerId = SellerId::of($command->sellerId);
        $status = OrderStatus::of($command->status);

        $order = $this->orderRepository->findByIdAndSeller($orderId, $sellerId);

        if (!$order instanceof Order) {
            throw new OrderNotFoundException($command->id);
        }

        $order->updateStatus(
            $status,
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
