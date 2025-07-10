<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Application\Command\SendInvoice;

use App\Invoice\Application\Command\SendInvoice\SendInvoiceCommand;
use App\Invoice\Application\Command\SendInvoice\SendInvoiceCommandHandler;
use App\Invoice\Domain\Event\InvoiceSentEvent;
use App\Invoice\Domain\Exception\InvoiceNotFoundException;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use App\Tests\Invoice\Infrastructure\Testing\Builders\InvoiceBuilder;
use App\Tests\Invoice\Infrastructure\Testing\Doubles\InvoiceRepositorySpy;
use App\Tests\Order\Infrastructure\Testing\Doubles\EventBusSpy;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SendInvoiceCommandHandlerTest extends TestCase
{
    private InvoiceRepositorySpy $invoiceRepository;
    private EventBusSpy $eventBus;
    private SendInvoiceCommandHandler $handler;

    private SellerId $sellerId;
    private OrderId $orderId;
    private DateTimeImmutable $validDate;

    protected function setUp(): void
    {
        $this->invoiceRepository = new InvoiceRepositorySpy();
        $this->eventBus = new EventBusSpy();
        $this->handler = new SendInvoiceCommandHandler($this->invoiceRepository, $this->eventBus);

        $this->sellerId = SellerId::generate();
        $this->orderId = OrderId::generate();
        $this->validDate = new DateTimeImmutable('2025-01-26T00:00:00Z');
    }

    protected function tearDown(): void
    {
        $this->invoiceRepository->clean();
        $this->eventBus->clean();
    }

    public function testShouldThrowInvoiceNotFoundExceptionWhenInvoiceDoesNotExist(): void
    {
        $command = new SendInvoiceCommand(
            $this->orderId->value(),
            $this->validDate
        );

        $this->expectException(InvoiceNotFoundException::class);

        ($this->handler)($command);

        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    public function testShouldUpdateInvoiceSentDateWhenInvoiceExists(): void
    {
        $invoice = InvoiceBuilder::anInvoice()
            ->withSellerId($this->sellerId)
            ->withOrderId($this->orderId)
            ->build();

        $this->invoiceRepository->add($invoice);

        $command = new SendInvoiceCommand(
            $this->orderId->value(),
            $this->validDate
        );

        ($this->handler)($command);

        $this->assertTrue($this->invoiceRepository->storeChanged());
        $this->assertCount(1, $this->invoiceRepository->stored());

        $updatedInvoice = $this->invoiceRepository->stored()[0];
        $this->assertTrue($updatedInvoice->sellerId()->equals($this->sellerId));
        $this->assertTrue($updatedInvoice->orderId()->equals($this->orderId));
        $this->assertNotNull($updatedInvoice->sentAt());
        $this->assertEquals($this->validDate->format('Y-m-d H:i:s'), $updatedInvoice->sentAt()->format());
    }

    public function testShouldDispatchInvoiceSentEventWhenInvoiceIsSent(): void
    {
        $invoice = InvoiceBuilder::anInvoice()
            ->withSellerId($this->sellerId)
            ->withOrderId($this->orderId)
            ->build();

        $this->invoiceRepository->add($invoice);

        $command = new SendInvoiceCommand(
            $this->orderId->value(),
            $this->validDate
        );

        ($this->handler)($command);

        $events = $this->eventBus->domainEvents();
        $this->assertCount(1, $events);

        $event = $events[0];
        $this->assertInstanceOf(InvoiceSentEvent::class, $event);
        $this->assertEquals($invoice->id()->value(), $event->aggregateId());
    }

    public function testShouldMarkInvoiceAsSentWhenProcessed(): void
    {
        $invoice = InvoiceBuilder::anInvoice()
            ->withSellerId($this->sellerId)
            ->withOrderId($this->orderId)
            ->build();

        $this->invoiceRepository->add($invoice);
        $this->assertFalse($invoice->isSent());

        $command = new SendInvoiceCommand(
            $this->orderId->value(),
            $this->validDate
        );

        ($this->handler)($command);

        $updatedInvoice = $this->invoiceRepository->stored()[0];
        $this->assertTrue($updatedInvoice->isSent());
    }
}