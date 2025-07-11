<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Application\Command\SendInvoice;

use App\Invoice\Application\Command\SendInvoice\SendInvoiceCommand;
use App\Invoice\Application\Command\SendInvoice\SendInvoiceCommandHandler;
use App\Invoice\Domain\Entity\Invoice;
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

    private const string VALID_DATE_STRING = '2025-01-26T10:30:00Z';

    protected function setUp(): void
    {
        $this->invoiceRepository = new InvoiceRepositorySpy();
        $this->eventBus = new EventBusSpy();
        $this->handler = new SendInvoiceCommandHandler($this->invoiceRepository, $this->eventBus);
    }

    protected function tearDown(): void
    {
        $this->invoiceRepository->clean();
        $this->eventBus->clean();
    }

    /**
     * @group send-invoice
     * @group exception-scenarios
     */
    public function testShouldThrowInvoiceNotFoundExceptionWhenInvoiceDoesNotExist(): void
    {
        // Given
        $nonExistentOrderId = OrderId::generate();
        $validDate = new DateTimeImmutable(self::VALID_DATE_STRING);
        
        $command = new SendInvoiceCommand(
            $nonExistentOrderId->value(),
            $validDate
        );

        // When & Then
        $this->expectException(InvoiceNotFoundException::class);
        $this->expectExceptionMessage($nonExistentOrderId->value());

        ($this->handler)($command);

        // Verify no side effects
        $this->assertFalse($this->invoiceRepository->storeChanged());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }


    /**
     * @group send-invoice
     * @group happy-path
     */
    public function testShouldSuccessfullyUpdateInvoiceSentDateWhenInvoiceExists(): void
    {
        // Given
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();
        $sentDate = new DateTimeImmutable(self::VALID_DATE_STRING);
        
        $originalInvoice = $this->givenAnExistingInvoiceForOrderAndSeller($orderId, $sellerId);
        $this->assertFalse($originalInvoice->isSent(), 'Invoice should not be sent initially');
        
        $command = new SendInvoiceCommand($orderId->value(), $sentDate);

        // When
        ($this->handler)($command);

        // Then
        $this->assertTrue($this->invoiceRepository->storeChanged());
        $this->assertCount(1, $this->invoiceRepository->stored());

        $updatedInvoice = $this->invoiceRepository->stored()[0];
        $this->assertTrue($updatedInvoice->sellerId()->equals($sellerId));
        $this->assertTrue($updatedInvoice->orderId()->equals($orderId));
        $this->assertTrue($updatedInvoice->isSent());
        $this->assertNotNull($updatedInvoice->sentAt());
        $this->assertEquals(
            $sentDate->format('Y-m-d H:i:s'), 
            $updatedInvoice->sentAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * @group send-invoice
     * @group events
     */
    public function testShouldDispatchInvoiceSentEventWithCorrectData(): void
    {
        // Given
        $orderId = OrderId::generate();
        $sentDate = new DateTimeImmutable(self::VALID_DATE_STRING);
        
        $invoice = $this->givenAnExistingInvoiceForOrder($orderId);
        $command = new SendInvoiceCommand($orderId->value(), $sentDate);

        // When
        ($this->handler)($command);

        // Then
        $events = $this->eventBus->domainEvents();
        $this->assertCount(1, $events);

        $event = $events[0];
        $this->assertInstanceOf(InvoiceSentEvent::class, $event);
        $this->assertEquals($invoice->id()->value(), $event->aggregateId());
        $this->assertInstanceOf(DateTimeImmutable::class, $event->occurredOn());
    }

    /**
     * @group send-invoice
     * @group state-changes
     */
    public function testShouldTransitionInvoiceFromUnsentToSentState(): void
    {
        // Given
        $orderId = OrderId::generate();
        $sentDate = new DateTimeImmutable(self::VALID_DATE_STRING);
        
        $invoice = $this->givenAnExistingInvoiceForOrder($orderId);
        $this->assertFalse($invoice->isSent(), 'Precondition: Invoice should not be sent');
        
        $command = new SendInvoiceCommand($orderId->value(), $sentDate);

        // When
        ($this->handler)($command);

        // Then
        $updatedInvoice = $this->invoiceRepository->stored()[0];
        $this->assertTrue($updatedInvoice->isSent(), 'Invoice should be marked as sent');
        
        // Verify the state transition is complete
        $sentAt = $updatedInvoice->sentAt();
        $this->assertNotNull($sentAt);
        $this->assertEquals(
            $sentDate->getTimestamp(),
            $sentAt->value()->getTimestamp()
        );
    }

    /**
     * @group send-invoice
     * @group edge-cases
     */
    public function testShouldHandleInvoiceAlreadyBeingSent(): void
    {
        // Given
        $orderId = OrderId::generate();
        $firstSentDate = new DateTimeImmutable('2025-01-25T10:00:00Z');
        $secondSentDate = new DateTimeImmutable(self::VALID_DATE_STRING);
        
        $invoice = $this->givenAnExistingInvoiceForOrder($orderId);
        
        // First send
        $firstCommand = new SendInvoiceCommand($orderId->value(), $firstSentDate);
        ($this->handler)($firstCommand);
        
        // Clear repository state for second attempt
        $this->invoiceRepository->clean();
        $this->invoiceRepository->add($this->invoiceRepository->stored()[0] ?? $invoice);
        
        // When - Second send attempt
        $secondCommand = new SendInvoiceCommand($orderId->value(), $secondSentDate);
        ($this->handler)($secondCommand);

        // Then - Should update the sent date (business decision: allow re-sending)
        $finalInvoice = $this->invoiceRepository->stored()[0];
        $this->assertTrue($finalInvoice->isSent());
        $this->assertEquals(
            $secondSentDate->format('Y-m-d H:i:s'),
            $finalInvoice->sentAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * Test helper: Creates an invoice for the given order ID
     */
    private function givenAnExistingInvoiceForOrder(OrderId $orderId): Invoice
    {
        $invoice = InvoiceBuilder::anInvoice()
            ->withOrderId($orderId)
            ->build();

        $this->invoiceRepository->add($invoice);
        
        return $invoice;
    }

    /**
     * Test helper: Creates an invoice for the given order and seller IDs
     */
    private function givenAnExistingInvoiceForOrderAndSeller(OrderId $orderId, SellerId $sellerId): Invoice
    {
        $invoice = InvoiceBuilder::anInvoice()
            ->withOrderId($orderId)
            ->withSellerId($sellerId)
            ->build();

        $this->invoiceRepository->add($invoice);
        
        return $invoice;
    }
}