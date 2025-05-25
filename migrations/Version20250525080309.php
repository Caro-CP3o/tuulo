<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250525080309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD avatar_id INT DEFAULT NULL, DROP avatar
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D64986383B10 FOREIGN KEY (avatar_id) REFERENCES media_object (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D64986383B10 ON user (avatar_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D64986383B10
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D64986383B10 ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD avatar VARCHAR(255) DEFAULT NULL, DROP avatar_id
        SQL);
    }
}
