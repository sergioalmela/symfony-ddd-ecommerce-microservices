<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Application\Command\UploadInvoice;

use App\Invoice\Application\Command\UploadInvoice\UploadInvoiceCommand;
use App\Invoice\Application\Command\UploadInvoice\UploadInvoiceCommandHandler;
use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Event\InvoiceUploadedEvent;
use App\Invoice\Domain\Exception\InvalidInvoiceFileTypeException;
use App\Invoice\Domain\Exception\InvoiceAlreadyExistsException;
use App\Invoice\Domain\Service\InvoiceFileValidator;
use App\Shared\Domain\Exception\OrderNotFoundException;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use App\Tests\Invoice\Infrastructure\Testing\Builders\InvoiceBuilder;
use App\Tests\Invoice\Infrastructure\Testing\Builders\OrderProjectionBuilder;
use App\Tests\Invoice\Infrastructure\Testing\Doubles\InvoiceRepositorySpy;
use App\Tests\Invoice\Infrastructure\Testing\Doubles\OrderProjectionRepositorySpy;
use App\Tests\Order\Infrastructure\Testing\Doubles\EventBusSpy;
use App\Tests\Shared\Infrastructure\Testing\Doubles\StorageServiceSpy;
use PHPUnit\Framework\TestCase;

final class UploadInvoiceCommandHandlerTest extends TestCase
{
    private InvoiceRepositorySpy $invoiceRepository;
    private OrderProjectionRepositorySpy $orderProjectionRepository;
    private EventBusSpy $eventBus;
    private StorageServiceSpy $storageService;
    private InvoiceFileValidator $invoiceFileValidator;
    private UploadInvoiceCommandHandler $handler;

    private const string VALID_FILE_CONTENT = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";
    private const string EXPECTED_BASE_URL = 'https://storage.example.com/';

    protected function setUp(): void
    {
        $this->invoiceRepository = new InvoiceRepositorySpy();
        $this->orderProjectionRepository = new OrderProjectionRepositorySpy();
        $this->eventBus = new EventBusSpy();
        $this->storageService = new StorageServiceSpy();
        $this->invoiceFileValidator = new InvoiceFileValidator();

        $this->handler = new UploadInvoiceCommandHandler(
            $this->invoiceRepository,
            $this->orderProjectionRepository,
            $this->eventBus,
            $this->storageService,
            $this->invoiceFileValidator
        );
    }

    protected function tearDown(): void
    {
        $this->invoiceRepository->clean();
        $this->orderProjectionRepository->clean();
        $this->eventBus->clean();
        $this->storageService->clean();
    }

    public function testShouldThrowInvoiceAlreadyExistsExceptionWhenInvoiceExists(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $existingInvoice = InvoiceBuilder::anInvoice()
            ->withOrderId($orderId)
            ->withSellerId($sellerId)
            ->build();
        $this->invoiceRepository->add($existingInvoice);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT
        );

        $this->expectException(InvoiceAlreadyExistsException::class);
        $this->expectExceptionMessage($orderId->value());

        ($this->handler)($command);

        $this->assertEmpty($this->storageService->getUploadedFiles());
        $this->assertFalse($this->invoiceRepository->storeChanged());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    public function testShouldThrowOrderNotFoundExceptionWhenOrderProjectionDoesNotExist(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT
        );

        $this->expectException(OrderNotFoundException::class);
        $this->expectExceptionMessage($orderId->value());

        ($this->handler)($command);

        $this->assertEmpty($this->storageService->getUploadedFiles());
        $this->assertFalse($this->invoiceRepository->storeChanged());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    public function testShouldSuccessfullyUploadInvoiceWhenValidData(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $orderProjection = OrderProjectionBuilder::anOrderProjection()
            ->withOrderId($orderId)
            ->withSellerId($sellerId)
            ->build();
        $this->orderProjectionRepository->add($orderProjection);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT
        );

        ($this->handler)($command);

        $this->assertTrue($this->invoiceRepository->storeChanged());
        $this->assertCount(1, $this->invoiceRepository->stored());

        $storedInvoice = $this->invoiceRepository->stored()[0];
        $this->assertTrue($storedInvoice->orderId()->equals($orderId));
        $this->assertTrue($storedInvoice->sellerId()->equals($sellerId));
        $this->assertNotNull($storedInvoice->id());
        $this->assertNotNull($storedInvoice->filePath());
    }

    public function testShouldUploadFileToStorageWithCorrectNameAndContent(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $this->givenAnExistingOrderProjection($orderId, $sellerId);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT
        );

        ($this->handler)($command);

        $uploadedFiles = $this->storageService->getUploadedFiles();
        $this->assertCount(1, $uploadedFiles);

        $uploadedFile = $uploadedFiles[0];
        $this->assertSame(self::VALID_FILE_CONTENT, $uploadedFile['content']);
        $this->assertStringContainsString('invoice-', $uploadedFile['fileName']);
        $this->assertStringContainsString("-order-{$orderId->value()}.pdf", $uploadedFile['fileName']);
    }

    public function testShouldStoreCorrectFileUrlInInvoice(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $this->givenAnExistingOrderProjection($orderId, $sellerId);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT
        );

        ($this->handler)($command);

        $storedInvoice = $this->invoiceRepository->stored()[0];
        $filePath = $storedInvoice->filePath()->value();

        $this->assertStringStartsWith(self::EXPECTED_BASE_URL, $filePath);
        $this->assertStringContainsString('invoice-', $filePath);
        $this->assertStringContainsString("-order-{$orderId->value()}.pdf", $filePath);
    }

    public function testShouldDispatchInvoiceUploadedEventAfterSuccessfulUpload(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $this->givenAnExistingOrderProjection($orderId, $sellerId);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT
        );

        ($this->handler)($command);

        $events = $this->eventBus->domainEvents();

        $this->assertCount(1, $events, 'Should emit exactly one InvoiceUploadedEvent');

        $event = $events[0];
        $this->assertInstanceOf(InvoiceUploadedEvent::class, $event);
        $this->assertSame($orderId->value(), $event->orderId());
        $this->assertSame($sellerId->value(), $event->sellerId());
        $this->assertStringContainsString($orderId->value(), $event->filePath());
        $this->assertStringContainsString('.pdf', $event->filePath());
        $this->assertSame('invoice.uploaded', $event->eventType());
        $this->assertSame(1, $event->eventVersion());

        $expectedPayload = [
            'invoiceId' => $event->aggregateId(),
            'orderId' => $orderId->value(),
            'sellerId' => $sellerId->value(),
            'filePath' => $event->filePath(),
        ];
        $this->assertSame($expectedPayload, $event->payload());
    }

    public function testShouldCreateInvoiceWithGeneratedIdAndCorrectProperties(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $this->givenAnExistingOrderProjection($orderId, $sellerId);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT
        );

        ($this->handler)($command);

        $storedInvoice = $this->invoiceRepository->stored()[0];

        $this->assertInstanceOf(Invoice::class, $storedInvoice);
        $this->assertNotEmpty($storedInvoice->id()->value());
        $this->assertTrue($storedInvoice->orderId()->equals($orderId));
        $this->assertTrue($storedInvoice->sellerId()->equals($sellerId));
        $this->assertFalse($storedInvoice->isSent());
        $this->assertNull($storedInvoice->sentAt());

        $this->assertStringContainsString('invoice-', $storedInvoice->filePath()->value());
        $this->assertStringContainsString($orderId->value(), $storedInvoice->filePath()->value());
        $this->assertStringContainsString('.pdf', $storedInvoice->filePath()->value());
    }

    public function testShouldRejectAnotherSellersInvoiceBeforeWritingFile(): void
    {
        $orderId = OrderId::generate();
        $this->givenAnExistingOrderProjection($orderId, SellerId::generate());
        try {
            ($this->handler)(new UploadInvoiceCommand($orderId->value(), SellerId::generate()->value(), self::VALID_FILE_CONTENT));
            $this->fail('Expected seller-scoped order lookup to reject upload');
        } catch (OrderNotFoundException) {
            $this->assertEmpty($this->storageService->getUploadedFiles());
            $this->assertFalse($this->invoiceRepository->storeChanged());
        }
    }

    public function testShouldRejectContentThatIsNotPdf(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();
        $this->givenAnExistingOrderProjection($orderId, $sellerId);
        $this->expectException(InvalidInvoiceFileTypeException::class);
        ($this->handler)(new UploadInvoiceCommand($orderId->value(), $sellerId->value(), 'plain text'));
    }

    /**
     * Test helper: Creates an order projection for the given order and seller.
     */
    private function givenAnExistingOrderProjection(OrderId $orderId, SellerId $sellerId): void
    {
        $orderProjection = OrderProjectionBuilder::anOrderProjection()
            ->withOrderId($orderId)
            ->withSellerId($sellerId)
            ->build();

        $this->orderProjectionRepository->add($orderProjection);
    }
}
