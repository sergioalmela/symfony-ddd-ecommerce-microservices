<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

interface StorageService
{
    public function uploadFile(string $fileContent, string $fileName): string;
}
