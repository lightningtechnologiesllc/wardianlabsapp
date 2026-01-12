<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111194459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create pending_stripe_installations table for storing Stripe OAuth data before user authentication';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE pending_stripe_installations (id VARCHAR(255) NOT NULL, linking_token VARCHAR(255) NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) NOT NULL, stripe_user_id VARCHAR(255) NOT NULL, publishable_key VARCHAR(255) NOT NULL, scope VARCHAR(255) NOT NULL, livemode BOOLEAN NOT NULL, token_type VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ED2A402B1A1E2A1E ON pending_stripe_installations (linking_token)');
        $this->addSql('CREATE INDEX IDX_STRIPE_USER_ID ON pending_stripe_installations (stripe_user_id)');
        $this->addSql('COMMENT ON COLUMN pending_stripe_installations.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN pending_stripe_installations.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN pending_stripe_installations.completed_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pending_stripe_installations');
    }
}
