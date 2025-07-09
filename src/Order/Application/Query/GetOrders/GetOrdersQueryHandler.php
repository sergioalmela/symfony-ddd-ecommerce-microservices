<?php

declare(strict_types=1);

namespace App\Order\Application\Query\GetOrders;

use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Domain\Bus\Query\QueryHandler;
use App\Shared\Domain\ValueObject\SellerId;

final readonly class GetOrdersQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {
    }

    public function __invoke(GetOrdersQuery $getOrdersQuery): GetOrdersResponse
    {
        $sellerId = SellerId::of($getOrdersQuery->sellerId);

        $orders = $this->orderRepository->findBySeller($sellerId);

        return new GetOrdersResponse(
            orders: $orders,
        );
    }
}
