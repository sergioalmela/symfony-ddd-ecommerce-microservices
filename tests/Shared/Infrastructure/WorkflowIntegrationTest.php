<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure;

use App\Invoice\Domain\Repository\InvoiceRepository;
use App\Order\Application\Command\CreateOrder\CreateOrderCommand;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\Event\OrderShippedEvent;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\SellerId;
use App\Shared\Infrastructure\Bus\Event\MessengerEventBus;
use App\Shared\Infrastructure\Bus\Event\OutboxPublisher;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

#[Group('integration')]
final class WorkflowIntegrationTest extends KernelTestCase
{
    private Connection $orderConnection;
    private Connection $invoiceConnection;
    private OrderId $orderId;
    private SellerId $sellerId;
    private CustomerId $customerId;

    protected function setUp(): void
    {
        if ('1' !== getenv('RUN_DATABASE_TESTS')) {
            $this->markTestSkipped('Set RUN_DATABASE_TESTS=1 with migrated, dedicated test databases');
        }
        self::bootKernel();
        $registry = self::getContainer()->get(ManagerRegistry::class);
        $this->orderConnection = $registry->getConnection('order');
        $this->invoiceConnection = $registry->getConnection('invoice');
        $this->orderConnection->beginTransaction();
        $this->invoiceConnection->beginTransaction();
        $this->orderId = OrderId::generate();
        $this->sellerId = SellerId::generate();
        $this->customerId = CustomerId::generate();
    }

    protected function tearDown(): void
    {
        if (isset($this->orderConnection)) {
            $this->orderConnection->rollBack();
            $this->invoiceConnection->rollBack();
        }
        parent::tearDown();
    }

    public function testHttpDetailsSellerScopeArrayResponseAndClientErrors(): void
    {
        $this->createOrder();
        $request = Request::create('/orders/'.$this->orderId->value().'?sellerId='.$this->sellerId->value());
        $response = self::$kernel->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($this->orderId->value(), json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR)['id']);
        $response = self::$kernel->handle(Request::create('/orders?sellerId='.$this->sellerId->value()));
        $data = json_decode($response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertTrue(array_is_list($data));
        $this->assertSame($this->orderId->value(), $data[0]['id']);
        $response = self::$kernel->handle(Request::create('/orders/'.$this->orderId->value().'?sellerId='.SellerId::generate()->value()));
        $this->assertSame(404, $response->getStatusCode());
        foreach (['/orders', '/orders?sellerId[]=invalid'] as $path) {
            $this->assertSame(400, self::$kernel->handle(Request::create($path))->getStatusCode());
        }
        foreach (['{', '{}', '{"quantity":"2"}', '[]'] as $body) {
            $response = self::$kernel->handle(Request::create('/orders', 'POST', content: $body));
            $this->assertSame(400, $response->getStatusCode());
        }
        foreach (['invalid' => 400, 'SHIPPED' => 409] as $status => $expected) {
            $response = self::$kernel->handle(Request::create('/orders/'.$this->orderId->value().'/status', 'PATCH', content: json_encode(['sellerId' => $this->sellerId->value(), 'status' => $status], \JSON_THROW_ON_ERROR)));
            $this->assertSame($expected, $response->getStatusCode());
        }
    }

    public function testStatusUpdateLoadsCurrentStateAndLocksTheOrder(): void
    {
        $this->createOrder();
        $this->orderConnection->executeStatement('UPDATE "order" SET status = ? WHERE id = ?', ['SHIPPING_IN_PROGRESS', $this->orderId->value()]);
        self::getContainer()->get(ManagerRegistry::class)->getManager('order')->clear();
        $order = self::getContainer()->get(OrderRepository::class)->findByIdAndSellerForUpdate($this->orderId, $this->sellerId);
        $this->assertSame('SHIPPING_IN_PROGRESS', $order->toPrimitives()['status']);
        $this->assertSame(1, (int) $this->orderConnection->fetchOne("SELECT COUNT(*) FROM pg_locks WHERE pid = pg_backend_pid() AND relation = '\"order\"'::regclass AND mode = 'RowShareLock'"));
    }

    #[DataProvider('arrivalOrders')]
    public function testEitherArrivalOrderAndRepeatedDelivery(bool $shipmentFirst): void
    {
        $this->createOrder();
        $shipment = OrderShippedEvent::create($this->orderId, $this->customerId);
        if ($shipmentFirst) {
            $this->receive(new Envelope($shipment));
        }
        $this->relay($this->orderConnection);
        $created = $this->transport()->getSent()[0];
        $this->receive($created);
        $this->receive($created);
        $this->assertSame(1, (int) $this->invoiceConnection->fetchOne('SELECT COUNT(*) FROM order_projection WHERE order_id = ?', [$this->orderId->value()]));
        $temporaryFile = tempnam(sys_get_temp_dir(), 'invoice-');
        file_put_contents($temporaryFile, 'not a PDF');
        $file = new UploadedFile($temporaryFile, 'invoice.pdf', 'application/pdf', test: true);
        $path = '/invoices/'.$this->orderId->value().'/upload';
        $response = self::$kernel->handle(Request::create($path, 'POST', ['sellerId' => $this->sellerId->value()], files: ['file' => $file]));
        $this->assertSame(400, $response->getStatusCode());
        file_put_contents($temporaryFile, "%PDF-1.4\n%%EOF\n");
        $response = self::$kernel->handle(Request::create($path, 'POST', ['sellerId' => SellerId::generate()->value()], files: ['file' => $file]));
        $this->assertSame(404, $response->getStatusCode());
        $response = self::$kernel->handle(Request::create($path, 'POST', ['sellerId' => mb_strtoupper($this->sellerId->value())], files: ['file' => $file]));
        $this->assertSame(201, $response->getStatusCode());
        $response = self::$kernel->handle(Request::create($path, 'POST', ['sellerId' => $this->sellerId->value()], files: ['file' => $file]));
        $this->assertSame(409, $response->getStatusCode());
        unlink($temporaryFile);
        $this->relay($this->invoiceConnection);
        $upload = $this->transport()->getSent()[0];
        $this->receive($upload);
        if (!$shipmentFirst) {
            $this->assertFalse(self::getContainer()->get(InvoiceRepository::class)->findByOrder($this->orderId)->isSent());
            $this->receive(new Envelope($shipment));
        }
        $invoice = self::getContainer()->get(InvoiceRepository::class)->findByOrder($this->orderId);
        $this->assertTrue($invoice->isSent());
        $firstSent = $invoice->sentAt()->value();
        $this->receive(new Envelope($shipment));
        $this->receive($upload);
        self::getContainer()->get(ManagerRegistry::class)->getManager('invoice')->clear();
        $reloaded = self::getContainer()->get(InvoiceRepository::class)->findByOrder($this->orderId);
        $this->assertSame($firstSent->getTimestamp(), $reloaded->sentAt()->value()->getTimestamp());
        $this->assertSame(1, (int) $this->invoiceConnection->fetchOne('SELECT COUNT(*) FROM outbox_message'));
        $this->relay($this->invoiceConnection);
        $sent = $this->transport()->getSent()[1];
        $this->receive($sent);
        $this->assertSame('invoice.sent', $sent->getMessage()->eventType());
        $path = self::getContainer()->getParameter('kernel.project_dir').'/public'.$invoice->filePath()->value();
        $this->assertSame("%PDF-1.4\n%%EOF\n", file_get_contents($path));
        unlink($path);
    }

    public static function arrivalOrders(): iterable
    {
        yield 'shipment before creation and upload' => [true];
        yield 'upload before shipment' => [false];
    }

    public function testRelayRetainsEventOnFailureAndRecoversWithSameEventId(): void
    {
        $this->createOrder();
        $eventId = $this->orderConnection->fetchOne('SELECT event_id FROM outbox_message');
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willThrowException(new RuntimeException('Broker unavailable'));
        $publisher = new OutboxPublisher(new MessengerEventBus($bus));
        try {
            $publisher->publishNext($this->orderConnection);
            $this->fail('Expected failed publication');
        } catch (RuntimeException) {
            $this->assertSame($eventId, $this->orderConnection->fetchOne('SELECT event_id FROM outbox_message'));
        }
        $this->relay($this->orderConnection);
        $this->assertSame($eventId, $this->transport()->getSent()[0]->getMessage()->eventId());
        $this->assertSame(0, (int) $this->orderConnection->fetchOne('SELECT COUNT(*) FROM outbox_message'));
    }

    public function testAggregateWriteRollsBackWhenOutboxRecordingFails(): void
    {
        $events = $this->createMock(EventBus::class);
        $events->method('publish')->willThrowException(new RuntimeException('Outbox unavailable'));
        self::getContainer()->set(EventBus::class, $events);
        try {
            $this->createOrder();
            $this->fail('Expected recording failure');
        } catch (RuntimeException) {
            $this->assertSame(0, (int) $this->orderConnection->fetchOne('SELECT COUNT(*) FROM "order" WHERE id = ?', [$this->orderId->value()]));
            $this->assertSame(0, (int) $this->orderConnection->fetchOne('SELECT COUNT(*) FROM outbox_message'));
        }
    }

    private function createOrder(): void
    {
        self::getContainer()->get(CommandBus::class)->dispatch(new CreateOrderCommand($this->orderId->value(), ProductId::generate()->value(), 2, 10.5, $this->customerId->value(), $this->sellerId->value()));
        $this->assertEmpty($this->transport()->getSent());
    }

    private function transport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');

        return $transport;
    }

    private function receive(Envelope $event): void
    {
        self::getContainer()->get('event.bus')->dispatch($event->with(new ReceivedStamp('async')));
    }

    private function relay(Connection $connection): void
    {
        $this->assertTrue(self::getContainer()->get(OutboxPublisher::class)->publishNext($connection));
    }
}
