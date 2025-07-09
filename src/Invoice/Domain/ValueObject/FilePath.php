<?php

declare(strict_types=1);

namespace App\Invoice\Domain\ValueObject;

use App\Invoice\Domain\Exception\InvalidFilePathException;

final readonly class FilePath
{
    private function __construct(
        private string $value
    ) {}

    public static function of(string $value): self
    {
        $trimmedValue = trim($value);
        
        if (empty($trimmedValue)) {
            throw new InvalidFilePathException('File path cannot be empty');
        }

        if (strlen($trimmedValue) > 500) {
            throw new InvalidFilePathException('File path cannot exceed 500 characters');
        }

        // Basic security check - prevent path traversal
        if (str_contains($trimmedValue, '..')) {
            throw new InvalidFilePathException('File path cannot contain ".." for security reasons');
        }

        // Ensure it has a valid file extension for invoices
        $allowedExtensions = ['pdf', 'html', 'xml', 'txt'];
        $extension = strtolower(pathinfo($trimmedValue, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new InvalidFilePathException(
                sprintf('File must have one of these extensions: %s', implode(', ', $allowedExtensions))
            );
        }

        return new self($trimmedValue);
    }

    public static function fromPrimitives(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->value, PATHINFO_EXTENSION));
    }

    public function filename(): string
    {
        return pathinfo($this->value, PATHINFO_BASENAME);
    }

    public function equals(FilePath $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}