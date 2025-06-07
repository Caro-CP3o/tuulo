<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250607080049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE post ADD image_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D3DA5256D FOREIGN KEY (image_id) REFERENCES media_object (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5A8A6C8D3DA5256D ON post (image_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D3DA5256D
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_5A8A6C8D3DA5256D ON post
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post DROP image_id
        SQL);
    }
}
