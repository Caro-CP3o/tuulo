<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250620085347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member DROP INDEX IDX_B9D4AD6DA76ED395, ADD UNIQUE INDEX unique_user (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX unique_family_user ON family_member
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family_member DROP INDEX unique_user, ADD INDEX IDX_B9D4AD6DA76ED395 (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX unique_family_user ON family_member (family_id, user_id)
        SQL);
    }
}
