<?php

declare(strict_types=1);

namespace Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add checkout_session_id to order for Stripe session expiry on order expiry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" ADD checkout_session_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP checkout_session_id');
    }
}
