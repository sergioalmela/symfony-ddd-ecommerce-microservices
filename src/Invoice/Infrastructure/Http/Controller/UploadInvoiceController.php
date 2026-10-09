<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Http\Controller;

use App\Invoice\Application\Command\UploadInvoice\UploadInvoiceCommand;
use App\Shared\Infrastructure\Http\Controller\BaseController;
use App\Shared\Infrastructure\Http\RequestData;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class UploadInvoiceController extends BaseController
{
    #[Route('/invoices/{orderId}/upload', name: 'upload_invoice', methods: ['POST'])]
    #[OA\Post(
        summary: 'Upload an invoice for a specific order',
        description: 'Upload an invoice file for a specific order',
        tags: ['Invoices']
    )]
    #[OA\Parameter(
        name: 'orderId',
        description: 'Unique identifier for the order',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: '80ebe6dd-fc51-41d5-ad53-59386d3ee6aa')
    )]
    #[OA\RequestBody(
        description: 'Invoice file upload data',
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                type: 'object',
                required: ['sellerId', 'file'],
                properties: [
                    new OA\Property(
                        property: 'sellerId',
                        type: 'string',
                        description: 'Seller unique identifier',
                        example: '50ebe6dd-fc51-41d5-ad53-59386d3ee6dd'
                    ),
                    new OA\Property(
                        property: 'file',
                        type: 'string',
                        format: 'binary',
                        description: 'Invoice file (PDF only)'
                    ),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'The invoice has been successfully uploaded.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Invoice uploaded successfully'),
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
    #[OA\Response(
        response: 409,
        description: 'Invoice already exists for this order.',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Invoice already exists for order'),
            ]
        )
    )]
    public function uploadInvoice(string $orderId, Request $request): JsonResponse
    {
        $sellerId = RequestData::form($request)->string('sellerId');
        $uploadedFile = $this->validateFile($request->files->get('file'));

        $uploadInvoiceCommand = new UploadInvoiceCommand(
            orderId: $orderId,
            sellerId: $sellerId,
            fileContent: $uploadedFile->getContent(),
        );

        $this->dispatch($uploadInvoiceCommand);

        return $this->jsonResponse(
            ['message' => 'Invoice uploaded successfully'],
            Response::HTTP_CREATED
        );
    }

    private function validateFile(mixed $uploadedFile): UploadedFile
    {
        if (!$uploadedFile instanceof UploadedFile || !$uploadedFile->isValid()) {
            throw new BadRequestHttpException('Valid file is required');
        }

        return $uploadedFile;
    }
}
