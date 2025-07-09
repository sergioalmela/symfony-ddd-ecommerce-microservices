<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Http\Controller;

use App\Order\Application\Command\UpdateOrderStatus\UpdateOrderStatusCommand;
use App\Shared\Infrastructure\Http\Controller\BaseController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateOrderStatusController extends BaseController
{
    #[Route('/orders/{id}/status', methods: ['PATCH'])]
    #[OA\Patch(
        summary: 'Update the status of an order',
        description: 'Update the status of a specific order, scoped to a seller.',
        tags: ['Orders']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'The ID of the order to update',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid', example: '80ebe6dd-fc51-41d5-ad53-59386d3ee6aa')
    )]
    #[OA\RequestBody(
        description: 'Data required to update the order status',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'SHIPPED'),
                new OA\Property(property: 'sellerId', type: 'string', example: '50ebe6dd-fc51-41d5-ad53-59386d3ee6dd'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'The order status has been successfully updated.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Order status updated successfully'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Invalid request data.')]
    #[OA\Response(response: 404, description: 'Order not found for the specified seller.')]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $updateOrderStatusCommand = new UpdateOrderStatusCommand(
            id: $id,
            sellerId: $data['sellerId'],
            status: $data['status'],
        );

        $this->dispatch($updateOrderStatusCommand);

        return $this->jsonResponse(['message' => 'Order status updated successfully'], Response::HTTP_OK);
    }
}
