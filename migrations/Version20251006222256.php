<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006222256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'tenant_price_to_roles_mapping_id_seq';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE tenant_price_to_roles_mapping_id_seq INCREMENT BY 1 MINVALUE 1 START 1;");
        $this->addSql("CREATE TABLE tenant_price_to_roles_mapping (id INT NOT NULL, tenant_id VARCHAR(255) NOT NULL, guild_id VARCHAR(255) NOT NULL, prices_to_roles_mapping JSON NOT NULL, PRIMARY KEY(id));");
        $this->addSql("CREATE UNIQUE INDEX tenant_and_guild ON tenant_price_to_roles_mapping (tenant_id, guild_id);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tenant_price_to_roles_mapping');
        $this->addSql('DROP SEQUENCE tenant_price_to_roles_mapping_id_seq');
    }
}
