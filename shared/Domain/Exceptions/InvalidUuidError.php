<?php

declare(strict_types=1);

namespace Shared\Domain\Exceptions;

use Shared\Domain\Exceptions\DomainException;

class InvalidUuidError extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct("Invalid UUID format: {$value}");
    }
}
