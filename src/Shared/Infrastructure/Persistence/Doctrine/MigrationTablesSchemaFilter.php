<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\AbstractNamedObject;
use Doctrine\DBAL\Schema\Name\OptionallyQualifiedName;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Hides the per-context migration tables from schema validation and updates. Migration commands
 * must still see them, so the filter only applies while one of those schema commands runs.
 */
final class MigrationTablesSchemaFilter
{
    private const array SCHEMA_COMMANDS = ['doctrine:schema:validate', 'doctrine:schema:update'];

    private bool $enabled = false;

    /**
     * @param list<string> $tableNames
     */
    public function __construct(
        private readonly array $tableNames,
    ) {
    }

    /**
     * @param AbstractAsset<OptionallyQualifiedName>|AbstractNamedObject<OptionallyQualifiedName>|string $asset
     */
    public function __invoke(AbstractAsset|AbstractNamedObject|string $asset): bool
    {
        if (!$this->enabled) {
            return true;
        }

        if ($asset instanceof AbstractNamedObject) {
            $asset = $asset->getObjectName()->toString();
        } elseif ($asset instanceof AbstractAsset) {
            $asset = $asset->getName();
        }

        return !\in_array($asset, $this->tableNames, true);
    }

    public function onConsoleCommand(ConsoleCommandEvent $consoleCommandEvent): void
    {
        if (\in_array($consoleCommandEvent->getCommand()?->getName(), self::SCHEMA_COMMANDS, true)) {
            $this->enabled = true;
        }
    }
}
