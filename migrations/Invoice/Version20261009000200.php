<?php

declare(strict_types=1);

namespace DoctrineMigrations\Invoice;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store projection identifiers as UUIDs and drop column type comments that DBAL 4 no longer reads';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_projection ALTER order_id TYPE UUID USING order_id::uuid');
        $this->addSql('ALTER TABLE order_projection ALTER seller_id TYPE UUID USING seller_id::uuid');
        $this->addSql('COMMENT ON COLUMN invoice.file_path IS NULL');
        $this->addSql('COMMENT ON COLUMN invoice.sent_at IS NULL');
        $this->addSql('COMMENT ON COLUMN order_projection.shipped_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN order_projection.shipped_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invoice.sent_at IS \'(DC2Type:datetime)\'');
        $this->addSql('COMMENT ON COLUMN invoice.file_path IS \'(DC2Type:string)\'');
        $this->addSql('ALTER TABLE order_projection ALTER seller_id TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE order_projection ALTER order_id TYPE VARCHAR(36)');
    }
}
