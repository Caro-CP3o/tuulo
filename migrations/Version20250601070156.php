<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250601070156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE post_media_object (post_id INT NOT NULL, media_object_id INT NOT NULL, INDEX IDX_BFE94CE14B89032C (post_id), INDEX IDX_BFE94CE164DE5A5 (media_object_id), PRIMARY KEY(post_id, media_object_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_media_object ADD CONSTRAINT FK_BFE94CE14B89032C FOREIGN KEY (post_id) REFERENCES post (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_media_object ADD CONSTRAINT FK_BFE94CE164DE5A5 FOREIGN KEY (media_object_id) REFERENCES media_object (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post ADD video_id INT DEFAULT NULL, DROP image, DROP video
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D29C1004E FOREIGN KEY (video_id) REFERENCES media_object (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5A8A6C8D29C1004E ON post (video_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE post_media_object DROP FOREIGN KEY FK_BFE94CE14B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_media_object DROP FOREIGN KEY FK_BFE94CE164DE5A5
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post_media_object
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8D29C1004E
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_5A8A6C8D29C1004E ON post
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post ADD image VARCHAR(255) DEFAULT NULL, ADD video VARCHAR(255) DEFAULT NULL, DROP video_id
        SQL);
    }
}
