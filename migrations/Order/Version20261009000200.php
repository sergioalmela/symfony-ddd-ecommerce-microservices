<?php

declare(strict_types=1);

namespace DoctrineMigrations\Order;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop column type comments that DBAL 4 no longer reads';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN "order".status IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN "order".status IS \'(DC2Type:string)\'');
    }
}
