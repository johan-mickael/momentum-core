<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cart and cart_item tables for pre-checkout ticket holds';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cart (id UUID NOT NULL, buyer_id UUID NOT NULL, event_id UUID NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_cart_status_expires_at ON cart (status, expires_at)');
        $this->addSql('CREATE INDEX idx_cart_buyer_event ON cart (buyer_id, event_id)');
        // Partial unique index: at most one ACTIVE cart per (buyer, event).
        // Doctrine's #[ORM\UniqueConstraint] cannot express a WHERE filter,
        // so this is raw SQL rather than an entity attribute. Expired and
        // checked-out carts are exempt, so buying history can accumulate
        // many rows per buyer/event without ever violating this index.
        $this->addSql("CREATE UNIQUE INDEX uniq_cart_active_buyer_event ON cart (buyer_id, event_id) WHERE (status = 'active')");

        $this->addSql('CREATE TABLE cart_item (id UUID NOT NULL, cart_id UUID NOT NULL, category_id UUID NOT NULL, quantity INT NOT NULL, added_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_cart_item_category ON cart_item (cart_id, category_id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_CART_ITEM_CART FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart_item DROP CONSTRAINT FK_CART_ITEM_CART');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE cart');
    }
}
