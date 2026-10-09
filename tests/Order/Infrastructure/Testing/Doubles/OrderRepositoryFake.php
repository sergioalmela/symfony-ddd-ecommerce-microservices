<?php

declare(strict_types=1);

namespace App\Tests\Order\Infrastructure\Testing\Doubles;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;

final class OrderRepositoryFake implements OrderRepository
{
    private array $orders = [];
    private bool $hasChanges = false;
    private array $storedOrders = [];

    public function add(Order $order): void
    {
        $this->orders[] = $order;
    }

    public function storeChanged(): bool
    {
        return $this->hasChanges;
    }

    public function stored(): array
    {
        return $this->storedOrders;
    }

    public function clean(): void
    {
        $this->orders = [];
        $this->hasChanges = false;
        $this->storedOrders = [];
    }

    public function find(OrderId $orderId): ?Order
    {
        foreach ($this->orders as $order) {
            if ($order->toPrimitives()['id'] === $orderId->value()) {
                return $order;
            }
        }

        return null;
    }

    public function findByIdAndSeller(OrderId $orderId, SellerId $sellerId): ?Order
    {
        foreach ($this->orders as $order) {
            $primitives = $order->toPrimitives();
            if ($primitives['id'] === $orderId->value() && $primitives['sellerId'] === $sellerId->value()) {
                return $order;
            }
        }

        return null;
    }

    public function findBySeller(SellerId $sellerId): array
    {
        $result = [];
        foreach ($this->orders as $order) {
            if ($order->toPrimitives()['sellerId'] === $sellerId->value()) {
                $result[] = $order;
            }
        }

        return $result;
    }

    public function save(Order $order): void
    {
        $this->hasChanges = true;
        $this->storedOrders[] = $order;
    }
}
