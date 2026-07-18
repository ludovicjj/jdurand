<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260718151331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE entry (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(32) NOT NULL, url LONGTEXT DEFAULT NULL, thumbnail_url LONGTEXT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, visibility TINYINT DEFAULT 1 NOT NULL, is_draft TINYINT DEFAULT 0 NOT NULL, token VARCHAR(255) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, external_id VARCHAR(100) DEFAULT NULL, provider VARCHAR(32) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entry_picture (id INT AUTO_INCREMENT NOT NULL, lightbox_path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) NOT NULL, position INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, entry_id INT NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_3A3DE01EBA364942 (entry_id), INDEX IDX_3A3DE01EB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE entry_picture ADD CONSTRAINT FK_3A3DE01EBA364942 FOREIGN KEY (entry_id) REFERENCES entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entry_picture ADD CONSTRAINT FK_3A3DE01EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE option_picture DROP FOREIGN KEY `FK_E2F58FCA7C41D6F`');
        $this->addSql('ALTER TABLE option_picture DROP FOREIGN KEY `FK_E2F58FCB03A8386`');
        $this->addSql('ALTER TABLE team_picture DROP FOREIGN KEY `FK_E9A23EBE296CD8AE`');
        $this->addSql('ALTER TABLE team_picture DROP FOREIGN KEY `FK_E9A23EBEB03A8386`');
        $this->addSql('DROP TABLE `option`');
        $this->addSql('DROP TABLE option_picture');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_picture');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `option` (id INT AUTO_INCREMENT NOT NULL, url LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, thumbnail_url LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, active TINYINT DEFAULT 1 NOT NULL, visibility TINYINT DEFAULT 1 NOT NULL, token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, position INT DEFAULT 0 NOT NULL, external_id VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, provider VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_draft TINYINT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE option_picture (id INT AUTO_INCREMENT NOT NULL, lightbox_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, thumbnail_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, position INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, option_id INT NOT NULL, INDEX IDX_E2F58FCA7C41D6F (option_id), INDEX IDX_E2F58FCB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, url LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, thumbnail_url LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, active TINYINT DEFAULT 1 NOT NULL, visibility TINYINT DEFAULT 1 NOT NULL, token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, position INT DEFAULT 0 NOT NULL, external_id VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, provider VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_draft TINYINT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE team_picture (id INT AUTO_INCREMENT NOT NULL, lightbox_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, thumbnail_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, position INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, team_id INT NOT NULL, INDEX IDX_E9A23EBE296CD8AE (team_id), INDEX IDX_E9A23EBEB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE option_picture ADD CONSTRAINT `FK_E2F58FCA7C41D6F` FOREIGN KEY (option_id) REFERENCES `option` (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE option_picture ADD CONSTRAINT `FK_E2F58FCB03A8386` FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE team_picture ADD CONSTRAINT `FK_E9A23EBE296CD8AE` FOREIGN KEY (team_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_picture ADD CONSTRAINT `FK_E9A23EBEB03A8386` FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE entry_picture DROP FOREIGN KEY FK_3A3DE01EBA364942');
        $this->addSql('ALTER TABLE entry_picture DROP FOREIGN KEY FK_3A3DE01EB03A8386');
        $this->addSql('DROP TABLE entry');
        $this->addSql('DROP TABLE entry_picture');
    }
}
