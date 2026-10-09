<?php

declare(strict_types=1);

namespace DoctrineMigrations\Invoice;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move recorded order shipments onto the order projection';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_projection ADD shipped_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN order_projection.shipped_at IS \'(DC2Type:datetimetz_immutable)\'');
        // Shipments recorded before their order projection have no seller and cannot be kept.
        $this->addSql('UPDATE order_projection SET shipped_at = order_shipment.shipped_at FROM order_shipment WHERE order_shipment.order_id::text = order_projection.order_id');
        $this->addSql('DROP TABLE order_shipment');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE order_shipment (order_id UUID PRIMARY KEY, shipped_at TIMESTAMP(6) WITH TIME ZONE NOT NULL)');
        $this->addSql('INSERT INTO order_shipment (order_id, shipped_at) SELECT order_id::uuid, shipped_at FROM order_projection WHERE shipped_at IS NOT NULL');
        $this->addSql('ALTER TABLE order_projection DROP shipped_at');
    }
}
