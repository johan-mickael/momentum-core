<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260808213714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add featured and featured_rank to event for curated homepage rails';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD featured BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE event ALTER COLUMN featured DROP DEFAULT');
        $this->addSql('ALTER TABLE event ADD featured_rank INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP featured');
        $this->addSql('ALTER TABLE event DROP featured_rank');
    }
}
