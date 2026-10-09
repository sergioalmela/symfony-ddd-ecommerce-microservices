<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Infrastructure\Persistence\Doctrine\MigrationTablesSchemaFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class MigrationTablesSchemaFilterTest extends TestCase
{
    private const array TABLE_NAMES = ['doctrine_migration_versions_order', 'doctrine_migration_versions_invoice'];

    public function testShouldKeepEveryTableOutsideSchemaCommands(): void
    {
        $migrationTablesSchemaFilter = new MigrationTablesSchemaFilter(self::TABLE_NAMES);
        $migrationTablesSchemaFilter->onConsoleCommand($this->eventFor('doctrine:migrations:migrate'));

        $this->assertTrue($migrationTablesSchemaFilter('doctrine_migration_versions_order'));
        $this->assertTrue($migrationTablesSchemaFilter('order'));
    }

    #[DataProvider('schemaCommands')]
    public function testShouldHideOnlyMigrationTablesDuringSchemaCommands(string $commandName): void
    {
        $migrationTablesSchemaFilter = new MigrationTablesSchemaFilter(self::TABLE_NAMES);
        $migrationTablesSchemaFilter->onConsoleCommand($this->eventFor($commandName));

        $this->assertFalse($migrationTablesSchemaFilter('doctrine_migration_versions_order'));
        $this->assertFalse($migrationTablesSchemaFilter('doctrine_migration_versions_invoice'));
        $this->assertTrue($migrationTablesSchemaFilter('order_projection'));
    }

    public static function schemaCommands(): iterable
    {
        yield 'validate' => ['doctrine:schema:validate'];
        yield 'update' => ['doctrine:schema:update'];
    }

    private function eventFor(string $commandName): ConsoleCommandEvent
    {
        return new ConsoleCommandEvent(new Command($commandName), new ArrayInput([]), new NullOutput());
    }
}
