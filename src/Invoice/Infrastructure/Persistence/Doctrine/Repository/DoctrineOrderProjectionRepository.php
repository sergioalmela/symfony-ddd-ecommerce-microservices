<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Persistence\Doctrine\Repository;

use App\Invoice\Domain\Entity\Projection\OrderProjection;
use App\Invoice\Domain\Repository\OrderProjectionRepository;
use App\Shared\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;

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
        $this->entityManager->persist($orderProjection);
        $this->entityManager->flush();
    }
}
