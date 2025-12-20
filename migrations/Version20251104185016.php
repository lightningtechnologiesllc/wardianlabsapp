<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104185016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_linking_tokens ALTER id TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE account_linking_tokens ALTER linking_token TYPE VARCHAR(255)');
        $this->addSql('ALTER INDEX uniq_linking_token RENAME TO UNIQ_B3EC28481A1E2A1E');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE account_linking_tokens ALTER id TYPE VARCHAR(26)');
        $this->addSql('ALTER TABLE account_linking_tokens ALTER linking_token TYPE VARCHAR(64)');
        $this->addSql('ALTER INDEX uniq_b3ec28481a1e2a1e RENAME TO uniq_linking_token');
    }
}
