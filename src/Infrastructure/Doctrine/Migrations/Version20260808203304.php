<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260808203304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add genre to event for search/filter taxonomy';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE event ADD genre VARCHAR(20) NOT NULL DEFAULT 'OTHER'");
        $this->addSql('ALTER TABLE event ALTER COLUMN genre DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP genre');
    }
}
