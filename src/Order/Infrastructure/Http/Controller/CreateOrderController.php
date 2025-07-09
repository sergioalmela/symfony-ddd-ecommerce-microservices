<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\Controller;

use App\Order\Application\Command\CreateOrder\CreateOrderCommand;
use App\Shared\Infrastructure\Http\Controller\BaseController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateOrderController extends BaseController
{
    #[Route('/orders', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create a new order',
        description: 'Create a new order for a customer',
        tags: ['Orders']
    )]
    #[OA\RequestBody(
        description: 'Order data required for creation',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'string', example: '80ebe6dd-fc51-41d5-ad53-59386d3ee6aa'),
                new OA\Property(property: 'productId', type: 'string', example: '70ebe6dd-fc51-41d5-ad53-59386d3ee6bb'),
                new OA\Property(property: 'quantity', type: 'integer', example: 2),
                new OA\Property(property: 'price', type: 'number', example: 29.99),
                new OA\Property(property: 'customerId', type: 'string', example: '60ebe6dd-fc51-41d5-ad53-59386d3ee6cc'),
                new OA\Property(property: 'sellerId', type: 'string', example: '50ebe6dd-fc51-41d5-ad53-59386d3ee6dd'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'The order has been successfully created.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Order created successfully'),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid request data.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Invalid request data'),
            ]
        )
    )]
    public function createOrder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $createOrderCommand = new CreateOrderCommand(
            id: $data['id'],
            productId: $data['productId'],
            quantity: $data['quantity'],
            price: $data['price'],
            customerId: $data['customerId'],
            sellerId: $data['sellerId']
        );

        $this->dispatch($createOrderCommand);

        return $this->jsonResponse(['message' => 'Order created successfully'], Response::HTTP_CREATED);
    }
}
