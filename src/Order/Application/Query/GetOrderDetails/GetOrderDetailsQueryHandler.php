<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrderDetails;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Domain\Bus\Query\QueryHandler;
use App\Shared\Domain\Exception\OrderNotFoundException;
use App\Shared\Domain\ValueObject\OrderId;

final readonly class GetOrderDetailsQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {
    }

    public function __invoke(GetOrderDetailsQuery $getOrderDetailsQuery): GetOrderDetailsResponse
    {
        $id = OrderId::of($getOrderDetailsQuery->id);

        $order = $this->orderRepository->find($id);

        if (!$order instanceof Order) {
            throw new OrderNotFoundException($getOrderDetailsQuery->id);
        }

        return new GetOrderDetailsResponse(
            order: $order,
        );
    }
}
