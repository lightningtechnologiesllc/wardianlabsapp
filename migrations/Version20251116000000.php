<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create pending_platform_subscriptions table for coupon-based platform subscription flow
 */
final class Version20251116000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create pending_platform_subscriptions table to store platform subscriptions with coupon codes before user account creation';
    }

    public function up(Schema $schema): void
    {
        // Create pending_platform_subscriptions table
        $this->addSql('
            CREATE TABLE pending_platform_subscriptions (
                id SERIAL PRIMARY KEY,
                pending_subscription_id VARCHAR(36) UNIQUE NOT NULL,
                customer_email VARCHAR(255) NOT NULL,
                coupon_code VARCHAR(20) UNIQUE NOT NULL,
                subscription_id VARCHAR(255) UNIQUE NOT NULL,
                plan_id VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                redeemed BOOLEAN DEFAULT FALSE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ');

        // Add indexes for efficient lookups
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PPS_COUPON_CODE ON pending_platform_subscriptions (coupon_code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PPS_SUBSCRIPTION_ID ON pending_platform_subscriptions (subscription_id)');
        $this->addSql('CREATE INDEX IDX_PPS_CUSTOMER_EMAIL ON pending_platform_subscriptions (customer_email)');
        $this->addSql('CREATE INDEX IDX_PPS_REDEEMED ON pending_platform_subscriptions (redeemed)');

        // Add Doctrine type comments for datetime fields
        $this->addSql('COMMENT ON COLUMN pending_platform_subscriptions.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN pending_platform_subscriptions.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes first
        $this->addSql('DROP INDEX IF EXISTS UNIQ_PPS_COUPON_CODE');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_PPS_SUBSCRIPTION_ID');
        $this->addSql('DROP INDEX IF EXISTS IDX_PPS_CUSTOMER_EMAIL');
        $this->addSql('DROP INDEX IF EXISTS IDX_PPS_REDEEMED');

        // Drop the table
        $this->addSql('DROP TABLE IF EXISTS pending_platform_subscriptions');
    }
}
