<?php

declare(strict_types=1);

namespace App\Tests\Order\Domain\Entity;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\OrderStatusTransitionInvalidException;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Order\Domain\ValueObject\Price;
use App\Order\Domain\ValueObject\Quantity;
use App\Shared\Domain\ValueObject\CustomerId;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\ProductId;
use App\Shared\Domain\ValueObject\SellerId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderLifecycleTest extends TestCase
{
    #[DataProvider('transitions')]
    public function testLifecycle(string $from, string $to, bool $allowed): void
    {
        $order = new Order(OrderId::generate(), ProductId::generate(), Quantity::of(1), Price::of(10), CustomerId::generate(), SellerId::generate(), OrderStatus::of($from));
        if (!$allowed) {
            try {
                $order->updateStatus(OrderStatus::of($to));
                $this->fail('Invalid transition was accepted');
            } catch (OrderStatusTransitionInvalidException) {
                $this->assertSame($from, $order->toPrimitives()['status']);
                $this->assertEmpty($order->getRecordedEvents());
            }

            return;
        }
        $order->updateStatus(OrderStatus::of($to));
        $this->assertSame($to, $order->toPrimitives()['status']);
        $this->assertCount('SHIPPED' === $to && $from !== $to ? 1 : 0, $order->getRecordedEvents());
    }

    public static function transitions(): iterable
    {
        $allowed = [
            'CREATED' => ['ACCEPTED', 'REJECTED'],
            'ACCEPTED' => ['SHIPPING_IN_PROGRESS', 'REJECTED'],
            'SHIPPING_IN_PROGRESS' => ['SHIPPED'],
            'REJECTED' => [],
            'SHIPPED' => [],
        ];
        foreach ($allowed as $from => $targets) {
            foreach (array_keys($allowed) as $to) {
                yield $from.' to '.$to => [$from, $to, $from === $to || \in_array($to, $targets, true)];
            }
        }
    }
}
