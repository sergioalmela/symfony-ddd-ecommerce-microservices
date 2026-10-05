<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Service;

use App\Shared\Domain\Service\StorageService;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;

final readonly class LocalStorageService implements StorageService
{
    public function __construct(
        private string $uploadDirectory,
    ) {
    }

    public function uploadFile(string $fileContent, string $fileName): string
    {
        if (1 !== preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/D', $fileName)) {
            throw new InvalidArgumentException('File name must be a safe basename');
        }

        $filePath = $this->uploadDirectory.'/'.$fileName;
        new Filesystem()->dumpFile($filePath, $fileContent);

        return '/uploads/'.$fileName;
    }
}
