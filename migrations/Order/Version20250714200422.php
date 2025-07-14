<?php

declare(strict_types=1);

namespace DoctrineMigrations\Order;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250714200422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix price column to use NUMERIC(10,2) for proper decimal precision';
    }

    public function up(Schema $schema): void
    {
        // Fix price column to support 2 decimal places for proper monetary precision
        $this->addSql('ALTER TABLE "order" ALTER COLUMN price TYPE NUMERIC(10,2)');
    }

    public function down(Schema $schema): void
    {
        // Revert back to NUMERIC(10,0) - this will lose decimal precision!
        $this->addSql('ALTER TABLE "order" ALTER COLUMN price TYPE NUMERIC(10,0)');
    }
}
