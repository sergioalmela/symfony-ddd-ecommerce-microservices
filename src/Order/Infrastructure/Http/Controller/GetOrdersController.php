<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\Controller;

use App\Order\Application\Query\GetOrders\GetOrdersQuery;
use App\Shared\Infrastructure\Http\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GetOrdersController extends BaseController
{
    #[Route('/orders', methods: ['GET'])]
    public function getOrders(Request $request): JsonResponse
    {
        $query = new GetOrdersQuery(
            sellerId: $request->query->get('sellerId', '80ebe6dd-fc51-41d5-ad53-59386d3ee6aa'),
        );

        $response = $this->ask($query);

        $orders = array_map(fn($order) => $order->toPrimitives(), $response->orders);

        return $this->jsonResponse($orders);
    }
}
