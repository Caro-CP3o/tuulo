<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514132713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, family_id INT NOT NULL, content LONGTEXT NOT NULL, title VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, video VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_5A8A6C8DF675F31B (author_id), INDEX IDX_5A8A6C8DC35E566A (family_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE post_comment (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, post_id INT NOT NULL, parent_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated VARCHAR(255) NOT NULL, INDEX IDX_A99CE55FA76ED395 (user_id), INDEX IDX_A99CE55F4B89032C (post_id), INDEX IDX_A99CE55F727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE post_like (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, post_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_653627B8A76ED395 (user_id), INDEX IDX_653627B84B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DC35E566A FOREIGN KEY (family_id) REFERENCES family (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55F4B89032C FOREIGN KEY (post_id) REFERENCES post (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55F727ACA70 FOREIGN KEY (parent_id) REFERENCES post_comment (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_like ADD CONSTRAINT FK_653627B8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_like ADD CONSTRAINT FK_653627B84B89032C FOREIGN KEY (post_id) REFERENCES post (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DF675F31B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DC35E566A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment DROP FOREIGN KEY FK_A99CE55FA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment DROP FOREIGN KEY FK_A99CE55F4B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_comment DROP FOREIGN KEY FK_A99CE55F727ACA70
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_like DROP FOREIGN KEY FK_653627B8A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_like DROP FOREIGN KEY FK_653627B84B89032C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post_comment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post_like
        SQL);
    }
}
