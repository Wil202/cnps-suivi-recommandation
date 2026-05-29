<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518080532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE departments (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, label VARCHAR(255) NOT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, structure_id INT NOT NULL, INDEX IDX_16AEB8D42534008B (structure_id), INDEX idx_department_code (code), INDEX idx_department_active (active), UNIQUE INDEX uniq_dept_code_per_structure (structure_id, code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE structures (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, label VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, chief_email VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_5BBEC55A77153098 (code), INDEX idx_structure_code (code), INDEX idx_structure_type (type), INDEX idx_structure_active (active), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE departments ADD CONSTRAINT FK_16AEB8D42534008B FOREIGN KEY (structure_id) REFERENCES structures (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departments DROP FOREIGN KEY FK_16AEB8D42534008B');
        $this->addSql('DROP TABLE departments');
        $this->addSql('DROP TABLE structures');
    }
}
