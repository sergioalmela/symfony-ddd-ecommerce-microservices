<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\Controller;

use App\Order\Application\Query\GetOrderDetails\GetOrderDetailsQuery;
use App\Shared\Infrastructure\Http\Controller\BaseController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
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
            )
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
            )
        ]
    )]
    public function __invoke(string $id): JsonResponse
    {
        $query = new GetOrderDetailsQuery($id);

        $order = $this->ask($query);

        return $this->jsonResponse($order->toPrimitives());
    }
}
