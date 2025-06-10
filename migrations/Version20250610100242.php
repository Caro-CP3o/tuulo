<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250610100242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family_invitation ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE used used TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C2D7B2DD77153098 ON family_invitation (code)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_C2D7B2DD77153098 ON family_invitation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_invitation DROP created_at, CHANGE used used TINYINT(1) NOT NULL
        SQL);
    }
}
