<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250601124851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE media_object ADD post_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media_object ADD CONSTRAINT FK_14D431324B89032C FOREIGN KEY (post_id) REFERENCES post (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_14D431324B89032C ON media_object (post_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE media_object DROP FOREIGN KEY FK_14D431324B89032C
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_14D431324B89032C ON media_object
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE media_object DROP post_id
        SQL);
    }
}
