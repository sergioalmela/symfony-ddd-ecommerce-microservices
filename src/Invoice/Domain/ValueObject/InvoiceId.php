<?php

declare(strict_types=1);

namespace App\Invoice\Domain\ValueObject;

use App\Shared\Domain\Entity\Uuid;

final readonly class InvoiceId extends Uuid
{
}
