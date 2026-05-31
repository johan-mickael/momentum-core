<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523135917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ticket (id UUID NOT NULL, event_id UUID NOT NULL, category_id UUID NOT NULL, holder_id UUID NOT NULL, price_amount INT NOT NULL, price_currency VARCHAR(3) NOT NULL, status VARCHAR(255) NOT NULL, reserved_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_ticket_event_id ON ticket (event_id)');
        $this->addSql('CREATE INDEX idx_ticket_holder_id ON ticket (holder_id)');
        $this->addSql('CREATE TABLE ticket_category (id UUID NOT NULL, name VARCHAR(255) NOT NULL, price_amount INT NOT NULL, price_currency VARCHAR(3) NOT NULL, total_quantity INT NOT NULL, reserved_quantity INT NOT NULL, event_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_8325E54071F7E88B ON ticket_category (event_id)');
        $this->addSql('ALTER TABLE ticket_category ADD CONSTRAINT FK_8325E54071F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ticket_category DROP CONSTRAINT FK_8325E54071F7E88B');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE ticket_category');
    }
}
