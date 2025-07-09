<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrderDetails;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Repository\OrderRepository;
use App\Order\Domain\ValueObject\OrderId;
use App\Shared\Domain\Bus\Query\QueryHandler;

final readonly class GetOrderDetailsQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {
    }

    public function __invoke(GetOrderDetailsQuery $query): GetOrderDetailsResponse
    {
        $id = OrderId::of($query->id);

        $order = $this->orderRepository->find($id);

        if (null === $order) {
            throw new OrderNotFoundException($query->id);
        }

        return new GetOrderDetailsResponse(
            order: $order,
        );
    }
}
