<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Service;

use App\Shared\Infrastructure\Service\LocalStorageService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

final class LocalStorageServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/invoice-test-'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->directory);
    }

    public function testStoresOriginalPdfBytesAndReturnsPublicPath(): void
    {
        $bytes = "%PDF-1.4\n%%EOF\n";
        $storage = new LocalStorageService($this->directory);
        $this->assertSame('/uploads/invoice.pdf', $storage->uploadFile($bytes, 'invoice.pdf'));
        $this->assertSame($bytes, file_get_contents($this->directory.'/invoice.pdf'));
    }

    public function testRejectsPathTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LocalStorageService($this->directory)->uploadFile('content', '../invoice.pdf');
    }

    public function testPropagatesWriteFailure(): void
    {
        file_put_contents($this->directory, 'not a directory');
        $this->expectException(IOException::class);
        new LocalStorageService($this->directory)->uploadFile('content', 'invoice.pdf');
    }
}
