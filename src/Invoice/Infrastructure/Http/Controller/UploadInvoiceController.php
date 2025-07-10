<?php

declare(strict_types=1);

namespace App\Invoice\Infrastructure\Http\Controller;

use App\Invoice\Application\Command\UploadInvoice\UploadInvoiceCommand;
use App\Shared\Infrastructure\Http\Controller\BaseController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
                        description: 'Invoice file (PDF, HTML, XML, or TXT)'
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
        $requestData = $this->extractRequestData($request);

        $uploadInvoiceCommand = new UploadInvoiceCommand(
            orderId: $orderId,
            sellerId: $requestData['sellerId'],
            fileContent: $requestData['fileContent'],
            fileExtension: $requestData['fileExtension']
        );

        $this->dispatch($uploadInvoiceCommand);

        return $this->jsonResponse(
            ['message' => 'Invoice uploaded successfully'],
            Response::HTTP_CREATED
        );
    }

    private function extractRequestData(Request $request): array
    {
        $sellerId = $request->request->get('sellerId');
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (!$sellerId) {
            throw new \InvalidArgumentException('Seller ID is required');
        }

        if (!$file || !$file->isValid()) {
            throw new \InvalidArgumentException('Valid file is required');
        }

        $fileExtension = $this->getFileExtension($file);

        return [
            'sellerId' => $sellerId,
            'fileContent' => $file->getContent(),
            'fileExtension' => $fileExtension,
        ];
    }

    private function getFileExtension(UploadedFile $file): string
    {
        // Try to get extension from original filename first
        $originalName = $file->getClientOriginalName();
        if ($originalName) {
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            if ($extension) {
                return strtolower($extension);
            }
        }

        // Fallback to MIME type mapping
        $mimeToExtension = [
            'application/pdf' => 'pdf',
            'text/html' => 'html',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'text/plain' => 'txt',
        ];

        $mimeType = $file->getMimeType();
        return $mimeToExtension[$mimeType] ?? 'pdf';
    }
}
