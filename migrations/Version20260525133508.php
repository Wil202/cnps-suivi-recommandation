<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525133508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recommendations (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(50) NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(10) NOT NULL, priority VARCHAR(20) NOT NULL, due_date DATE DEFAULT NULL, created_at DATETIME NOT NULL, meeting_id INT DEFAULT NULL, assigned_structure_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_73904ED7AEA34913 (reference), INDEX IDX_73904ED767433D9C (meeting_id), INDEX IDX_73904ED7C7AB407F (assigned_structure_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE recommendations ADD CONSTRAINT FK_73904ED767433D9C FOREIGN KEY (meeting_id) REFERENCES meetings (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recommendations ADD CONSTRAINT FK_73904ED7C7AB407F FOREIGN KEY (assigned_structure_id) REFERENCES structures (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recommendations DROP FOREIGN KEY FK_73904ED767433D9C');
        $this->addSql('ALTER TABLE recommendations DROP FOREIGN KEY FK_73904ED7C7AB407F');
        $this->addSql('DROP TABLE recommendations');
    }
}
