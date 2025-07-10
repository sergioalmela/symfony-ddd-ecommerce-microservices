<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Service;

use App\Shared\Domain\Service\StorageService;

final readonly class LocalStorageService implements StorageService
{
    public function __construct(
        private string $uploadDirectory = '/tmp/uploads'
    ) {
    }

    public function uploadFile(string $fileContent, string $fileName): string
    {
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }

        $filePath = $this->uploadDirectory . '/' . $fileName;
        file_put_contents($filePath, $fileContent);

        return '/uploads/' . $fileName;
    }
}