<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Infrastructure\Testing\Doubles;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Shared\Domain\ValueObject\OrderId;

final class OrderProjectionRepositorySpy implements OrderProjectionRepository
{
    private array $orderProjections = [];
    private bool $hasChanges = false;
    private array $stored = [];

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

    public function save(OrderProjection $orderProjection): void
    {
        $this->hasChanges = true;
        $this->stored[] = $orderProjection;
    }
}
