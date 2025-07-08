<?php

declare(strict_types=1);

namespace DoctrineMigrations\Order;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250708110539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "order" (id UUID NOT NULL, product_id UUID NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 0) NOT NULL, customer_id UUID NOT NULL, seller_id UUID NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_order_seller_id ON "order" (seller_id)');
        $this->addSql('CREATE INDEX idx_order_seller_id_id ON "order" (seller_id, id)');
        $this->addSql('COMMENT ON COLUMN "order".status IS \'(DC2Type:string)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE "order"');
    }
}
