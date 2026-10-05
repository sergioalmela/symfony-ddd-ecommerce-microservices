<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Doctrine\Repository;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Shared\Domain\ValueObject\OrderId;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final readonly class DoctrineOrderProjectionRepository implements OrderProjectionRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function find(OrderId $orderId): ?OrderProjection
    {
        return $this->entityManager
            ->getRepository(OrderProjection::class)
            ->findOneBy(['orderId' => $orderId]);
    }

    public function save(OrderProjection $orderProjection): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO order_projection (order_id, seller_id) VALUES (:orderId, :sellerId) ON CONFLICT (order_id) DO NOTHING',
            ['orderId' => $orderProjection->orderId()->value(), 'sellerId' => $orderProjection->sellerId()->value()]
        );
    }

    public function recordShipment(OrderId $orderId, DateTimeImmutable $shippedAt): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO order_shipment (order_id, shipped_at) VALUES (:orderId, :shippedAt) ON CONFLICT (order_id) DO NOTHING',
            ['orderId' => $orderId->value(), 'shippedAt' => $shippedAt->format('Y-m-d H:i:s.uP')]
        );
    }

    public function isShipped(OrderId $orderId): bool
    {
        return false !== $this->entityManager->getConnection()->fetchOne(
            'SELECT order_id FROM order_shipment WHERE order_id = :orderId',
            ['orderId' => $orderId->value()]
        );
    }

    public function lock(OrderId $orderId): void
    {
        $connection = $this->entityManager->getConnection();
        if (!$connection->isTransactionActive()) {
            throw new LogicException('Invoice workflow locking requires an active transaction');
        }

        // The order projection may not exist yet when shipment arrives first.
        $connection->executeQuery('SELECT pg_advisory_xact_lock(hashtextextended(:orderId, 0))', ['orderId' => $orderId->value()]);
    }
}
