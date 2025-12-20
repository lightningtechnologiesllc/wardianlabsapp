<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250816000118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE members_id_seq INCREMENT BY 1 MINVALUE 1 START 1;");
        $this->addSql("CREATE TABLE members (id INT NOT NULL, member_id VARCHAR(255) NOT NULL, tenant_id VARCHAR(255) NOT NULL, guild_id VARCHAR(255) NOT NULL, discord_user_id VARCHAR(255) NOT NULL, stripe_user_email VARCHAR(255) NOT NULL, subscriptions JSON NOT NULL, roles_to_assign JSON NOT NULL, PRIMARY KEY(id));");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_45A0D2FF7597D3FE ON members (member_id);");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE members_id_seq');
        $this->addSql('DROP TABLE members');

    }
}
