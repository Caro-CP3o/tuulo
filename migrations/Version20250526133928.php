<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250526133928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family ADD cover_image_id INT DEFAULT NULL, DROP cover_image
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family ADD CONSTRAINT FK_A5E6215BE5A0E336 FOREIGN KEY (cover_image_id) REFERENCES media_object (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A5E6215BE5A0E336 ON family (cover_image_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family DROP FOREIGN KEY FK_A5E6215BE5A0E336
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_A5E6215BE5A0E336 ON family
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family ADD cover_image VARCHAR(255) DEFAULT NULL, DROP cover_image_id
        SQL);
    }
}
