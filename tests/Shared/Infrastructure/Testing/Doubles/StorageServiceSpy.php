<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Testing\Doubles;

use App\Shared\Domain\Service\StorageService;
use RuntimeException;

final class StorageServiceSpy implements StorageService
{
    private array $uploadedFiles = [];
    private string $baseUrl = 'https://storage.example.com/';

    public function uploadFile(string $fileContent, string $fileName): string
    {
        $this->uploadedFiles[] = [
            'content' => $fileContent,
            'fileName' => $fileName,
        ];

        return $this->baseUrl.$fileName;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function clean(): void
    {
        $this->uploadedFiles = [];
    }

    public function simulateUploadFailure(): void
    {
        throw new RuntimeException('Storage service unavailable');
    }
}
