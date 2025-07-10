<?php

declare(strict_types=1);

namespace DoctrineMigrations\Invoice;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250710095038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoice (id UUID NOT NULL, order_id UUID NOT NULL, seller_id UUID NOT NULL, file_path VARCHAR(255) NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_invoice_seller_id ON invoice (seller_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_invoice_order_seller ON invoice (order_id, seller_id)');
        $this->addSql('COMMENT ON COLUMN invoice.file_path IS \'(DC2Type:string)\'');
        $this->addSql('COMMENT ON COLUMN invoice.sent_at IS \'(DC2Type:datetime)\'');
        $this->addSql('DROP TABLE "order"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE "order" (id UUID NOT NULL, product_id UUID NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 0) NOT NULL, customer_id UUID NOT NULL, seller_id UUID NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_order_seller_id ON "order" (seller_id)');
        $this->addSql('CREATE INDEX idx_order_seller_id_id ON "order" (seller_id, id)');
        $this->addSql('DROP TABLE invoice');
    }
}
