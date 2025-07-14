<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\Controller;

use App\Order\Application\Query\GetOrderDetails\GetOrderDetailsQuery;
use App\Shared\Infrastructure\Http\Controller\BaseController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GetOrderDetailsController extends BaseController
{
    #[Route('/orders/{id}', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get order details',
        description: 'Retrieve the details of a specific order by its ID',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'The ID of the order to retrieve',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: 'a7a4f438-e4b7-4123-9a39-435345e274f4')
            ),
            new OA\Parameter(
                name: 'sellerId',
                description: 'The seller ID to filter the order',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '50ebe6dd-fc51-41d5-ad53-59386d3ee6dd')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Details of the order',
                content: new OA\JsonContent(
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
            ),
            new OA\Response(
                response: 404,
                description: 'Order not found'
            ),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $getOrderDetailsQuery = new GetOrderDetailsQuery(
            $id,
            $request->query->get('sellerId')
        );

        $order = $this->ask($getOrderDetailsQuery);

        return $this->jsonResponse($order->toPrimitives());
    }
}
