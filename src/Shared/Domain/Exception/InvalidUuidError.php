<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

class InvalidUuidError extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct("Invalid UUID format: {$value}");
    }
}
