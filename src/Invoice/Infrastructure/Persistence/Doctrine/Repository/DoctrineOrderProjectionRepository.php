<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Doctrine\Repository;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Shared\Domain\ValueObject\OrderId;
use App\Shared\Domain\ValueObject\SellerId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final readonly class DoctrineOrderProjectionRepository implements OrderProjectionRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findForUpdate(OrderId $orderId): ?OrderProjection
    {
        $connection = $this->entityManager->getConnection();
        if (!$connection->isTransactionActive()) {
            throw new LogicException('Locking an order projection requires an active transaction');
        }

        $row = $connection->fetchAssociative(
            'SELECT order_id, seller_id, shipped_at FROM order_projection WHERE order_id = :orderId FOR UPDATE',
            ['orderId' => $orderId->value()]
        );
        if (false === $row) {
            return null;
        }

        return new OrderProjection(
            OrderId::fromPrimitives((string) $row['order_id']),
            SellerId::fromPrimitives((string) $row['seller_id']),
            $connection->convertToPHPValue($row['shipped_at'], Types::DATETIMETZ_IMMUTABLE),
        );
    }

    public function save(OrderProjection $orderProjection): void
    {
        // Either the creation or the shipment event may insert the row first; keep the first shipment time.
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO order_projection (order_id, seller_id, shipped_at) VALUES (:orderId, :sellerId, :shippedAt)
             ON CONFLICT (order_id) DO UPDATE SET shipped_at = COALESCE(order_projection.shipped_at, EXCLUDED.shipped_at)',
            [
                'orderId' => $orderProjection->orderId()->value(),
                'sellerId' => $orderProjection->sellerId()->value(),
                'shippedAt' => $orderProjection->shippedAt(),
            ],
            ['shippedAt' => Types::DATETIMETZ_IMMUTABLE]
        );
    }
}
