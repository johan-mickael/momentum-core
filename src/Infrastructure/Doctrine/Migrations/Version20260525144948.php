<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525144948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add entry_code column to ticket table for QR check-in';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket ADD entry_code VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE ticket SET entry_code = gen_random_uuid()::VARCHAR WHERE entry_code IS NULL');
        $this->addSql('ALTER TABLE ticket ALTER COLUMN entry_code SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_ticket_entry_code ON ticket (entry_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_ticket_entry_code');
        $this->addSql('ALTER TABLE ticket DROP entry_code');
    }
}
