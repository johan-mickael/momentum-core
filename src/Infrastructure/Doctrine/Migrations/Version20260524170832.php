<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260524170832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "order" and order_item tables for the Order aggregate';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "order" (id UUID NOT NULL, buyer_id UUID NOT NULL, event_id UUID NOT NULL, status VARCHAR(255) NOT NULL, total_amount INT NOT NULL, total_currency VARCHAR(3) NOT NULL, placed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_order_buyer_id ON "order" (buyer_id)');
        $this->addSql('CREATE INDEX idx_order_status_expires_at ON "order" (status, expires_at)');
        $this->addSql('CREATE TABLE order_item (id UUID NOT NULL, category_id UUID NOT NULL, ticket_id UUID NOT NULL, unit_price_amount INT NOT NULL, unit_price_currency VARCHAR(3) NOT NULL, order_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_52EA1F09700047D2 ON order_item (ticket_id)');
        $this->addSql('CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item DROP CONSTRAINT FK_52EA1F098D9F6D38');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE order_item');
    }
}
