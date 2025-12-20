<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251102005535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create account_linking_tokens table for Stripe subscription to Discord linking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE account_linking_tokens (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(255) NOT NULL,
            stripe_subscription_id VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            stripe_price_id VARCHAR(255) NOT NULL,
            linking_token VARCHAR(64) NOT NULL,
            discord_user_id VARCHAR(255) DEFAULT NULL,
            linked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_LINKING_TOKEN ON account_linking_tokens (linking_token)');
        $this->addSql('CREATE INDEX IDX_STRIPE_SUBSCRIPTION ON account_linking_tokens (stripe_subscription_id)');
        $this->addSql('CREATE INDEX IDX_CUSTOMER_EMAIL ON account_linking_tokens (customer_email)');
        $this->addSql('COMMENT ON COLUMN account_linking_tokens.linked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN account_linking_tokens.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN account_linking_tokens.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE account_linking_tokens');
    }
}
