<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250917232532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE stripe_accounts_id_seq INCREMENT BY 1 MINVALUE 1 START 1;");
        $this->addSql("CREATE TABLE stripe_accounts (id INT NOT NULL, account_id VARCHAR(255) NOT NULL, tenant_id VARCHAR(255) NOT NULL, stripe_provider_account_id VARCHAR(255) NOT NULL, display_name VARCHAR(255) NOT NULL, stripe_user_id VARCHAR(255) NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) NOT NULL, publishable_key VARCHAR(255) NOT NULL, scope VARCHAR(255) NOT NULL, livemode BOOLEAN NOT NULL, token_type VARCHAR(255) NOT NULL, PRIMARY KEY(id));");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_978F429F9B6B5FBA ON stripe_accounts (account_id);");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_978F429F3EA8D901 ON stripe_accounts (stripe_provider_account_id);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE stripe_accounts_id_seq');
        $this->addSql('DROP TABLE stripe_accounts');
    }
}
