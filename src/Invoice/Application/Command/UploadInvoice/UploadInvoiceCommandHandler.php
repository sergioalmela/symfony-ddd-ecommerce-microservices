<?php

declare(strict_types=1);

namespace App\Invoice\Application\Command\UploadInvoice;

use App\Invoice\Domain\Exception\InvoiceAlreadyExistsException;
use App\Invoice\Domain\Repository\InvoiceRepository;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\SellerId;

final readonly class UploadInvoiceCommandHandler implements CommandHandler
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(UploadInvoiceCommand $uploadInvoiceCommand): void
    {
        $orderId = OrderId::of($uploadInvoiceCommand->orderId);
        $sellerId = SellerId::of($uploadInvoiceCommand->sellerId);

        if ($this->invoiceRepository->findByOrderAndSeller($orderId, $sellerId) !== null) {
            throw new InvoiceAlreadyExistsException($uploadInvoiceCommand->orderId);
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
