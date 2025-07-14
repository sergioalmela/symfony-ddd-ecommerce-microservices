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

    private const string VALID_FILE_CONTENT = 'PDF_FILE_CONTENT_HERE';
    private const string VALID_MIME_TYPE = 'application/pdf';
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

    /**
     * @group upload-invoice
     * @group exception-scenarios
     */
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
            self::VALID_FILE_CONTENT,
            self::VALID_MIME_TYPE
        );

        $this->expectException(InvoiceAlreadyExistsException::class);
        $this->expectExceptionMessage($orderId->value());

        ($this->handler)($command);

        $this->assertEmpty($this->storageService->getUploadedFiles());
        $this->assertFalse($this->invoiceRepository->storeChanged());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    /**
     * @group upload-invoice
     * @group exception-scenarios
     */
    public function testShouldThrowOrderNotFoundExceptionWhenOrderProjectionDoesNotExist(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_MIME_TYPE
        );

        $this->expectException(OrderNotFoundException::class);
        $this->expectExceptionMessage($orderId->value());

        ($this->handler)($command);

        $this->assertEmpty($this->storageService->getUploadedFiles());
        $this->assertFalse($this->invoiceRepository->storeChanged());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    /**
     * @group upload-invoice
     * @group happy-path
     */
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
            self::VALID_FILE_CONTENT,
            self::VALID_MIME_TYPE
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

    /**
     * @group upload-invoice
     * @group file-storage
     */
    public function testShouldUploadFileToStorageWithCorrectNameAndContent(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $this->givenAnExistingOrderProjection($orderId, $sellerId);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_MIME_TYPE
        );

        ($this->handler)($command);

        $uploadedFiles = $this->storageService->getUploadedFiles();
        $this->assertCount(1, $uploadedFiles);

        $uploadedFile = $uploadedFiles[0];
        $this->assertSame(self::VALID_FILE_CONTENT, $uploadedFile['content']);
        $this->assertStringContainsString('invoice-', $uploadedFile['fileName']);
        $this->assertStringContainsString("-order-{$orderId->value()}.pdf", $uploadedFile['fileName']);
    }

    /**
     * @group upload-invoice
     * @group file-storage
     */
    public function testShouldStoreCorrectFileUrlInInvoice(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $this->givenAnExistingOrderProjection($orderId, $sellerId);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_MIME_TYPE
        );

        ($this->handler)($command);

        $storedInvoice = $this->invoiceRepository->stored()[0];
        $filePath = $storedInvoice->filePath()->value();

        $this->assertStringStartsWith(self::EXPECTED_BASE_URL, $filePath);
        $this->assertStringContainsString('invoice-', $filePath);
        $this->assertStringContainsString("-order-{$orderId->value()}.pdf", $filePath);
    }

    /**
     * @group upload-invoice
     * @group events
     */
    public function testShouldDispatchInvoiceUploadedEventAfterSuccessfulUpload(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $this->givenAnExistingOrderProjection($orderId, $sellerId);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_MIME_TYPE
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
    }

    /**
     * @group upload-invoice
     * @group validation
     */
    public function testShouldThrowExceptionWhenInvalidMimeType(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $this->givenAnExistingOrderProjection($orderId, $sellerId);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            'application/msword'
        );

        $this->expectException(InvalidInvoiceFileTypeException::class);
        $this->expectExceptionMessage('Invoice files must be PDF');

        ($this->handler)($command);

        $this->assertEmpty($this->storageService->getUploadedFiles());
        $this->assertFalse($this->invoiceRepository->storeChanged());
        $this->assertCount(0, $this->eventBus->domainEvents());
    }

    /**
     * @group upload-invoice
     * @group invoice-properties
     */
    public function testShouldCreateInvoiceWithGeneratedIdAndCorrectProperties(): void
    {
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();

        $this->givenAnExistingOrderProjection($orderId, $sellerId);

        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_MIME_TYPE
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
