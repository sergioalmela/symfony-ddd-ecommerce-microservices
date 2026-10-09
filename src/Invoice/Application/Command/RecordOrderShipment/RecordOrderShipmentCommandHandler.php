<?php

declare(strict_types=1);

namespace App\Invoice\Application\Command\RecordOrderShipment;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final readonly class RecordOrderShipmentCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderProjectionRepository $orderProjectionRepository,
    ) {
    }

    public function __invoke(RecordOrderShipmentCommand $recordOrderShipmentCommand): void
    {
        $orderId = OrderId::of($recordOrderShipmentCommand->orderId);
        $sellerId = SellerId::of($recordOrderShipmentCommand->sellerId);

        // The shipment can arrive before the creation event, so it may create the projection.
        $orderProjection = $this->orderProjectionRepository->findForUpdate($orderId)
            ?? new OrderProjection($orderId, $sellerId);

        $orderProjection->markShipped($recordOrderShipmentCommand->shippedAt);

        $this->orderProjectionRepository->save($orderProjection);
    }
}
