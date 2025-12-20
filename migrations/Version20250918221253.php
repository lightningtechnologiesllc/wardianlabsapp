<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250918221253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1;");
        $this->addSql("CREATE TABLE users (id INT NOT NULL, user_id VARCHAR(255) NOT NULL, discord_user_id VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, global_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, avatar VARCHAR(255) NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) NOT NULL, expires_on BIGINT NOT NULL, scope VARCHAR(255) NOT NULL, token_type VARCHAR(255) NOT NULL, PRIMARY KEY(id));");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_1483A5E9A76ED395 ON users (user_id);");

        $this->addSql("CREATE SEQUENCE tenants_id_seq INCREMENT BY 1 MINVALUE 1 START 1;");
        $this->addSql("CREATE TABLE tenants (id INT NOT NULL, owner_id INT DEFAULT NULL, tenant_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, subdomain VARCHAR(255) NOT NULL, email_dsn VARCHAR(255) NOT NULL, email_from_address VARCHAR(255) NOT NULL, PRIMARY KEY(id));");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_B8FC96BB9033212A ON tenants (tenant_id);");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_B8FC96BBC1D5962E ON tenants (subdomain);");
        $this->addSql("CREATE INDEX IDX_B8FC96BB7E3C61F9 ON tenants (owner_id);");
        $this->addSql("ALTER TABLE tenants ADD CONSTRAINT FK_B8FC96BB7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE;");

    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tenants');
        $this->addSql('DROP SEQUENCE tenants_id_seq');

        $this->addSql('DROP TABLE users');
        $this->addSql('DROP SEQUENCE users_id_seq');
    }
}
