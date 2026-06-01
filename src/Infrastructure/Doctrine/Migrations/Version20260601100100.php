<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601100100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category_name to order_item for human-readable Stripe line items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE order_item ADD category_name VARCHAR(255) NOT NULL DEFAULT 'Unknown'");
        $this->addSql('ALTER TABLE order_item ALTER COLUMN category_name DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_item DROP category_name');
    }
}
