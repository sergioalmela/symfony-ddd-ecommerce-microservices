<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Entity\Projection;

use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use DateTimeImmutable;

final class OrderProjection
{
    public function __construct(
        private readonly OrderId $orderId,
        private readonly SellerId $sellerId,
        private ?DateTimeImmutable $shippedAt = null,
    ) {
    }

    public static function create(
        string $orderId,
        string $sellerId,
    ): self {
        return new self(
            OrderId::of($orderId),
            SellerId::of($sellerId)
        );
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function sellerId(): SellerId
    {
        return $this->sellerId;
    }

    public function markShipped(DateTimeImmutable $shippedAt): void
    {
        $this->shippedAt ??= $shippedAt;
    }

    public function isShipped(): bool
    {
        return $this->shippedAt instanceof DateTimeImmutable;
    }

    public function shippedAt(): ?DateTimeImmutable
    {
        return $this->shippedAt;
    }
}
