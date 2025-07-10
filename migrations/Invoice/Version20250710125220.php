<?php

declare(strict_types=1);

namespace DoctrineMigrations\Invoice;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250710125220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order_projections table for Invoice context';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE order_projection (
            order_id VARCHAR(36) NOT NULL,
            seller_id VARCHAR(36) NOT NULL,
            PRIMARY KEY(order_id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_projection');
    }
}
