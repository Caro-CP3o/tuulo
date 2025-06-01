<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250601104308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE post_media_object DROP FOREIGN KEY FK_BFE94CE14B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_media_object DROP FOREIGN KEY FK_BFE94CE164DE5A5
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post_media_object
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE post_media_object (post_id INT NOT NULL, media_object_id INT NOT NULL, INDEX IDX_BFE94CE14B89032C (post_id), INDEX IDX_BFE94CE164DE5A5 (media_object_id), PRIMARY KEY(post_id, media_object_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_media_object ADD CONSTRAINT FK_BFE94CE14B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_media_object ADD CONSTRAINT FK_BFE94CE164DE5A5 FOREIGN KEY (media_object_id) REFERENCES media_object (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
    }
}
