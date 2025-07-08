<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250708091508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "order" ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE "order" ALTER product_id TYPE UUID');
        $this->addSql('ALTER TABLE "order" ALTER quantity TYPE INT');
        $this->addSql('ALTER TABLE "order" ALTER price TYPE NUMERIC(10, 0)');
        $this->addSql('ALTER TABLE "order" ALTER customer_id TYPE UUID');
        $this->addSql('ALTER TABLE "order" ALTER seller_id TYPE UUID');
        $this->addSql('ALTER TABLE "order" ALTER status TYPE VARCHAR(255)');
        $this->addSql('COMMENT ON COLUMN "order".status IS \'(DC2Type:string)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "order" ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE "order" ALTER product_id TYPE UUID');
        $this->addSql('ALTER TABLE "order" ALTER quantity TYPE INT');
        $this->addSql('ALTER TABLE "order" ALTER price TYPE NUMERIC(10, 0)');
        $this->addSql('ALTER TABLE "order" ALTER customer_id TYPE UUID');
        $this->addSql('ALTER TABLE "order" ALTER seller_id TYPE UUID');
        $this->addSql('ALTER TABLE "order" ALTER status TYPE VARCHAR(255)');
        $this->addSql('COMMENT ON COLUMN "order".status IS NULL');
    }
}
