<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Listener;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Shared\Domain\Bus\Event\EventHandler;
use App\Shared\Domain\Event\OrderCreatedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateOrderProjectionEventHandler implements EventHandler
{
    public function __construct(
        private OrderProjectionRepository $orderProjectionRepository,
    ) {
    }

    public function __invoke(OrderCreatedEvent $orderCreatedEvent): void
    {
        $orderProjection = OrderProjection::create(
            $orderCreatedEvent->aggregateId(),
            $orderCreatedEvent->sellerId->value(),
        );

        $this->orderProjectionRepository->save($orderProjection);
    }
}
