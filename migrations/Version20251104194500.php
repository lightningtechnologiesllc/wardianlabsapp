<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add webhook_secret and webhook_endpoint_id to stripe_accounts table
 */
final class Version20251104194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add webhook_secret and webhook_endpoint_id columns to stripe_accounts table for per-account webhook verification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stripe_accounts ADD webhook_secret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE stripe_accounts ADD webhook_endpoint_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stripe_accounts DROP webhook_secret');
        $this->addSql('ALTER TABLE stripe_accounts DROP webhook_endpoint_id');
    }
}
