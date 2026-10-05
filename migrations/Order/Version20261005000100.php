<?php

declare(strict_types=1);

namespace DoctrineMigrations\Order;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store outgoing Order events transactionally';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE outbox_message (event_id UUID PRIMARY KEY, sequence BIGSERIAL NOT NULL UNIQUE, body TEXT NOT NULL, created_at TIMESTAMP(6) WITH TIME ZONE NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE outbox_message');
    }
}
