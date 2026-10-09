<?php

declare(strict_types=1);

namespace DoctrineMigrations\Order;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Version orders to reject concurrent status changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" ADD version INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP version');
    }
}
