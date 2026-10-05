<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Infrastructure\Testing\Doubles;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Shared\Domain\ValueObject\OrderId;
use DateTimeImmutable;

final class OrderProjectionRepositorySpy implements OrderProjectionRepository
{
    private array $orderProjections = [];
    private bool $hasChanges = false;
    private array $stored = [];
    private array $shipments = [];

    public function add(OrderProjection $orderProjection): void
    {
        $this->orderProjections[] = $orderProjection;
    }

    public function storeChanged(): bool
    {
        return $this->hasChanges;
    }

    public function storedProjections(): array
    {
        return $this->stored;
    }

    public function clean(): void
    {
        $this->orderProjections = [];
        $this->hasChanges = false;
        $this->stored = [];
        $this->shipments = [];
    }

    public function find(OrderId $orderId): ?OrderProjection
    {
        foreach ($this->orderProjections as $projection) {
            if ($projection->orderId()->equals($orderId)) {
                return $projection;
            }
        }

        return null;
    }

    public function lock(OrderId $orderId): void
    {
    }

    public function recordShipment(OrderId $orderId, DateTimeImmutable $shippedAt): void
    {
        $this->shipments[$orderId->value()] ??= $shippedAt;
    }

    public function isShipped(OrderId $orderId): bool
    {
        return isset($this->shipments[$orderId->value()]);
    }

    public function save(OrderProjection $orderProjection): void
    {
        $this->hasChanges = true;
        $this->stored[] = $orderProjection;
    }
}
