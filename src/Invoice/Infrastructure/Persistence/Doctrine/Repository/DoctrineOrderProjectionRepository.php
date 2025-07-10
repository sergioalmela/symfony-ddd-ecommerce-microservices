<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Doctrine\Repository;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Shared\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOrderProjectionRepository implements OrderProjectionRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(OrderProjection $orderProjection): void
    {
        $this->entityManager->persist($orderProjection);
        $this->entityManager->flush();
    }

    public function findByOrderId(OrderId $orderId): ?OrderProjection
    {
        return $this->entityManager
            ->getRepository(OrderProjection::class)
            ->findOneBy(['orderId' => $orderId]);
    }

    public function exists(OrderId $orderId): bool
    {
        return $this->findByOrderId($orderId) !== null;
    }

    public function update(OrderProjection $orderProjection): void
    {
        $this->entityManager->flush();
    }
}
