<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250627065514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family DROP INDEX IDX_A5E6215BE5A0E336, ADD UNIQUE INDEX UNIQ_A5E6215BE5A0E336 (cover_image_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family DROP FOREIGN KEY FK_A5E6215B61220EA6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family DROP FOREIGN KEY FK_A5E6215BE5A0E336
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family ADD CONSTRAINT FK_A5E6215B61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family ADD CONSTRAINT FK_A5E6215BE5A0E336 FOREIGN KEY (cover_image_id) REFERENCES media_object (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member DROP INDEX IDX_B9D4AD6DA76ED395, ADD UNIQUE INDEX unique_user (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member DROP FOREIGN KEY FK_B9D4AD6DC35E566A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member ADD CONSTRAINT FK_B9D4AD6DC35E566A FOREIGN KEY (family_id) REFERENCES family (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family DROP INDEX UNIQ_A5E6215BE5A0E336, ADD INDEX IDX_A5E6215BE5A0E336 (cover_image_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family DROP FOREIGN KEY FK_A5E6215BE5A0E336
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family DROP FOREIGN KEY FK_A5E6215B61220EA6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family ADD CONSTRAINT FK_A5E6215BE5A0E336 FOREIGN KEY (cover_image_id) REFERENCES media_object (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family ADD CONSTRAINT FK_A5E6215B61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member DROP INDEX unique_user, ADD INDEX IDX_B9D4AD6DA76ED395 (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member DROP FOREIGN KEY FK_B9D4AD6DC35E566A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member ADD CONSTRAINT FK_B9D4AD6DC35E566A FOREIGN KEY (family_id) REFERENCES family (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
    }
}
