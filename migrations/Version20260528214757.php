<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528214757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recommendations ADD assigned_agent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recommendations ADD CONSTRAINT FK_73904ED749197702 FOREIGN KEY (assigned_agent_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_73904ED749197702 ON recommendations (assigned_agent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recommendations DROP FOREIGN KEY FK_73904ED749197702');
        $this->addSql('DROP INDEX IDX_73904ED749197702 ON recommendations');
        $this->addSql('ALTER TABLE recommendations DROP assigned_agent_id');
    }
}
