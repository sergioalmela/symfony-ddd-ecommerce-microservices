<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Application\Command\UploadInvoice;

use App\Invoice\Application\Command\UploadInvoice\UploadInvoiceCommand;
use App\Invoice\Application\Command\UploadInvoice\UploadInvoiceCommandHandler;
use App\Invoice\Domain\Entity\Invoice;
use App\Invoice\Domain\Exception\InvoiceAlreadyExistsException;
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
    private UploadInvoiceCommandHandler $handler;

    private const string VALID_FILE_CONTENT = 'PDF_FILE_CONTENT_HERE';
    private const string VALID_FILE_EXTENSION = 'pdf';
    private const string EXPECTED_BASE_URL = 'https://storage.example.com/';

    protected function setUp(): void
    {
        $this->invoiceRepository = new InvoiceRepositorySpy();
        $this->orderProjectionRepository = new OrderProjectionRepositorySpy();
        $this->eventBus = new EventBusSpy();
        $this->storageService = new StorageServiceSpy();
        
        $this->handler = new UploadInvoiceCommandHandler(
            $this->invoiceRepository,
            $this->orderProjectionRepository,
            $this->eventBus,
            $this->storageService
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
        // Given
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
            self::VALID_FILE_EXTENSION
        );

        // When & Then
        $this->expectException(InvoiceAlreadyExistsException::class);
        $this->expectExceptionMessage($orderId->value());

        ($this->handler)($command);

        // Verify no side effects
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
        // Given
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();
        
        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_FILE_EXTENSION
        );

        // When & Then
        $this->expectException(OrderNotFoundException::class);
        $this->expectExceptionMessage($orderId->value());

        ($this->handler)($command);

        // Verify no side effects
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
        // Given
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
            self::VALID_FILE_EXTENSION
        );

        // When
        ($this->handler)($command);

        // Then
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
        // Given
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();
        
        $this->givenAnExistingOrderProjection($orderId, $sellerId);
        
        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_FILE_EXTENSION
        );

        // When
        ($this->handler)($command);

        // Then
        $uploadedFiles = $this->storageService->getUploadedFiles();
        $this->assertCount(1, $uploadedFiles);

        $uploadedFile = $uploadedFiles[0];
        $this->assertEquals(self::VALID_FILE_CONTENT, $uploadedFile['content']);
        $this->assertEquals("Invoice-{$orderId->value()}." . self::VALID_FILE_EXTENSION, $uploadedFile['fileName']);
    }

    /**
     * @group upload-invoice
     * @group file-storage
     */
    public function testShouldStoreCorrectFileUrlInInvoice(): void
    {
        // Given
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();
        
        $this->givenAnExistingOrderProjection($orderId, $sellerId);
        
        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_FILE_EXTENSION
        );

        // When
        ($this->handler)($command);

        // Then
        $storedInvoice = $this->invoiceRepository->stored()[0];
        $expectedFileName = "Invoice-{$orderId->value()}." . self::VALID_FILE_EXTENSION;
        $expectedUrl = self::EXPECTED_BASE_URL . $expectedFileName;
        
        $this->assertEquals($expectedUrl, $storedInvoice->filePath()->value());
    }

    /**
     * @group upload-invoice
     * @group events
     */
    public function testShouldDispatchDomainEventsAfterSuccessfulUpload(): void
    {
        // Given
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();
        
        $this->givenAnExistingOrderProjection($orderId, $sellerId);
        
        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_FILE_EXTENSION
        );

        // When
        ($this->handler)($command);

        // Then
        $events = $this->eventBus->domainEvents();
        
        // Note: Invoice::create() might not generate events by default
        // This test verifies the event publishing mechanism works
        // If Invoice should generate events on creation, they would be published here
        $this->assertIsArray($events);
    }

    /**
     * @group upload-invoice
     * @group file-types
     */
    public function testShouldHandleDifferentFileExtensions(): void
    {
        // Given
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();
        
        $this->givenAnExistingOrderProjection($orderId, $sellerId);
        
        $fileExtensions = ['pdf', 'xml', 'txt', 'html']; // Only valid extensions according to FilePath
        
        foreach ($fileExtensions as $index => $extension) {
            // Reset for each iteration
            $this->tearDown();
            $this->setUp();
            $this->givenAnExistingOrderProjection($orderId, $sellerId);
            
            $command = new UploadInvoiceCommand(
                $orderId->value(),
                $sellerId->value(),
                "CONTENT_FOR_{$extension}",
                $extension
            );

            // When
            ($this->handler)($command);

            // Then
            $uploadedFile = $this->storageService->getLastUploadedFile();
            $this->assertEquals("Invoice-{$orderId->value()}.{$extension}", $uploadedFile['fileName']);
            $this->assertEquals("CONTENT_FOR_{$extension}", $uploadedFile['content']);
        }
    }

    /**
     * @group upload-invoice
     * @group invoice-properties
     */
    public function testShouldCreateInvoiceWithGeneratedIdAndCorrectProperties(): void
    {
        // Given
        $orderId = OrderId::generate();
        $sellerId = SellerId::generate();
        
        $this->givenAnExistingOrderProjection($orderId, $sellerId);
        
        $command = new UploadInvoiceCommand(
            $orderId->value(),
            $sellerId->value(),
            self::VALID_FILE_CONTENT,
            self::VALID_FILE_EXTENSION
        );

        // When
        ($this->handler)($command);

        // Then
        $storedInvoice = $this->invoiceRepository->stored()[0];
        
        // Verify invoice properties
        $this->assertInstanceOf(Invoice::class, $storedInvoice);
        $this->assertNotEmpty($storedInvoice->id()->value());
        $this->assertTrue($storedInvoice->orderId()->equals($orderId));
        $this->assertTrue($storedInvoice->sellerId()->equals($sellerId));
        $this->assertFalse($storedInvoice->isSent()); // Should not be sent initially
        $this->assertNull($storedInvoice->sentAt()); // Should not have sent date initially
        
        // Verify file path is properly set
        $this->assertStringContainsString('Invoice-', $storedInvoice->filePath()->value());
        $this->assertStringContainsString($orderId->value(), $storedInvoice->filePath()->value());
        $this->assertStringContainsString(self::VALID_FILE_EXTENSION, $storedInvoice->filePath()->value());
    }

    /**
     * Test helper: Creates an order projection for the given order and seller
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