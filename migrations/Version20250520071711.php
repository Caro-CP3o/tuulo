<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250520071711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family ADD creator_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family ADD CONSTRAINT FK_A5E6215B61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A5E6215B61220EA6 ON family (creator_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family DROP FOREIGN KEY FK_A5E6215B61220EA6
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_A5E6215B61220EA6 ON family
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family DROP creator_id
        SQL);
    }
}
