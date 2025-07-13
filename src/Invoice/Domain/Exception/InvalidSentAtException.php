<?php

declare(strict_types=1);

namespace App\Invoice\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidSentAtException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
