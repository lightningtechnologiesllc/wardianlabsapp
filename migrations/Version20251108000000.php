<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Update members table to support new Member aggregate structure with linking tokens and guild memberships
 */
final class Version20251108000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update members table: rename columns, add linking token fields, add timestamps, make discord_user_id nullable';
    }

    public function up(Schema $schema): void
    {
        // Add new columns with default values for existing records
        $this->addSql('ALTER TABLE members ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOW() NOT NULL');
        $this->addSql('ALTER TABLE members ADD linking_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE members ADD linking_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE members ADD linked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // Rename stripe_user_email to customer_email
        $this->addSql('ALTER TABLE members RENAME COLUMN stripe_user_email TO customer_email');

        // Add guild_memberships column and migrate data from guild_id and roles_to_assign
        $this->addSql('ALTER TABLE members ADD guild_memberships JSON NOT NULL DEFAULT \'[]\'');

        // Migrate existing data: combine guild_id and roles_to_assign into guild_memberships
        $this->addSql("
            UPDATE members
            SET guild_memberships = jsonb_build_array(
                jsonb_build_object(
                    'guild_id', guild_id,
                    'roles', roles_to_assign
                )
            )
            WHERE guild_id IS NOT NULL
        ");

        // Drop old columns
        $this->addSql('ALTER TABLE members DROP COLUMN guild_id');
        $this->addSql('ALTER TABLE members DROP COLUMN roles_to_assign');

        // Make discord_user_id nullable (it should be for pending members)
        $this->addSql('ALTER TABLE members ALTER COLUMN discord_user_id DROP NOT NULL');

        // Add indexes for efficient lookups
        $this->addSql('CREATE UNIQUE INDEX UNIQ_45A0D2FF1A1E2A1E ON members (linking_token) WHERE linking_token IS NOT NULL');
        $this->addSql('CREATE INDEX IDX_45A0D2FF5C9BA49E ON members (customer_email)');

        // Add timestamp column comments
        $this->addSql('COMMENT ON COLUMN members.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN members.linking_token_expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN members.linked_at IS \'(DC2Type:datetime_immutable)\'');

        // Remove the default value from created_at now that existing rows have values
        $this->addSql('ALTER TABLE members ALTER COLUMN created_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // Add back guild_id and roles_to_assign
        $this->addSql('ALTER TABLE members ADD guild_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE members ADD roles_to_assign JSON DEFAULT \'[]\'');

        // Migrate data back from guild_memberships (take first membership)
        $this->addSql("
            UPDATE members
            SET
                guild_id = (guild_memberships->0->>'guild_id')::VARCHAR,
                roles_to_assign = (guild_memberships->0->'roles')::JSON
            WHERE jsonb_array_length(guild_memberships) > 0
        ");

        // Make guild_id and roles_to_assign NOT NULL
        $this->addSql('ALTER TABLE members ALTER COLUMN guild_id SET NOT NULL');
        $this->addSql('ALTER TABLE members ALTER COLUMN roles_to_assign SET NOT NULL');

        // Drop guild_memberships column
        $this->addSql('ALTER TABLE members DROP COLUMN guild_memberships');

        // Rename customer_email back to stripe_user_email
        $this->addSql('ALTER TABLE members RENAME COLUMN customer_email TO stripe_user_email');

        // Make discord_user_id NOT NULL again
        $this->addSql('ALTER TABLE members ALTER COLUMN discord_user_id SET NOT NULL');

        // Drop indexes
        $this->addSql('DROP INDEX UNIQ_45A0D2FF1A1E2A1E');
        $this->addSql('DROP INDEX IDX_45A0D2FF5C9BA49E');

        // Drop new columns
        $this->addSql('ALTER TABLE members DROP COLUMN created_at');
        $this->addSql('ALTER TABLE members DROP COLUMN linking_token');
        $this->addSql('ALTER TABLE members DROP COLUMN linking_token_expires_at');
        $this->addSql('ALTER TABLE members DROP COLUMN linked_at');
    }
}
