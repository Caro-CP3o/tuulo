<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611073142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D64971CCADAF
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D64971CCADAF ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP pending_invitation_id, DROP status, CHANGE registration_step registration_step VARCHAR(20) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD pending_invitation_id INT DEFAULT NULL, ADD status VARCHAR(20) DEFAULT 'pending' NOT NULL, CHANGE registration_step registration_step VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D64971CCADAF FOREIGN KEY (pending_invitation_id) REFERENCES family_invitation (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D64971CCADAF ON user (pending_invitation_id)
        SQL);
    }
}
