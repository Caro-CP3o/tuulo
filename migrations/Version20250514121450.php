<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514121450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE family (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, cover_image VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, join_code VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE family_member (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, family_id INT NOT NULL, joined_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_B9D4AD6DA76ED395 (user_id), INDEX IDX_B9D4AD6DC35E566A (family_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member ADD CONSTRAINT FK_B9D4AD6DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member ADD CONSTRAINT FK_B9D4AD6DC35E566A FOREIGN KEY (family_id) REFERENCES family (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member DROP FOREIGN KEY FK_B9D4AD6DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member DROP FOREIGN KEY FK_B9D4AD6DC35E566A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE family
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE family_member
        SQL);
    }
}
