<?php

declare(strict_types=1);

namespace App\Tests\Invoice\Domain\Service;

use App\Invoice\Domain\Exception\InvalidInvoiceFileContentException;
use App\Invoice\Domain\Service\InvoiceFileValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InvoiceFileValidatorTest extends TestCase
{
    #[DataProvider('invalidSizes')]
    public function testRejectsEmptyOrOversizedFile(string $content): void
    {
        $this->expectException(InvalidInvoiceFileContentException::class);
        new InvoiceFileValidator()->validate('application/pdf', $content);
    }

    public static function invalidSizes(): iterable
    {
        yield 'empty' => [''];
        yield 'oversized' => ["%PDF-1.4\n".str_repeat(' ', 10485760)];
        yield 'oversized multibyte content' => ["%PDF-1.4\n".str_repeat("\xc3\xa9", 5242880)];
    }
}
