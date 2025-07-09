<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\Controller;

use App\Order\Application\Query\GetOrders\GetOrdersQuery;
use App\Shared\Infrastructure\Http\Controller\BaseController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GetOrdersController extends BaseController
{
    #[Route('/orders', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get orders',
        description: 'Retrieve a list of orders for a seller',
        tags: ['Orders']
    )]
    #[OA\Parameter(
        name: 'sellerId',
        description: 'The seller ID to filter orders',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: '50ebe6dd-fc51-41d5-ad53-59386d3ee6dd')
    )]
    #[OA\Response(
        response: 200,
        description: 'List of orders',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'productId', type: 'string'),
                    new OA\Property(property: 'quantity', type: 'integer'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'customerId', type: 'string'),
                    new OA\Property(property: 'sellerId', type: 'string'),
                    new OA\Property(property: 'status', type: 'string'),
                ]
            )
        )
    )]
    public function getOrders(Request $request): JsonResponse
    {
        $getOrdersQuery = new GetOrdersQuery(
            sellerId: $request->query->get('sellerId'),
        );

        $response = $this->ask($getOrdersQuery);

        $orders = array_map(fn ($order) => $order->toPrimitives(), $response->orders);

        return $this->jsonResponse($orders);
    }
}
