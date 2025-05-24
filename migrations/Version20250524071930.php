<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250524071930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE family_invitation (id INT AUTO_INCREMENT NOT NULL, family_id INT NOT NULL, code VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', used TINYINT(1) NOT NULL, INDEX IDX_C2D7B2DDC35E566A (family_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE family_invitation ADD CONSTRAINT FK_C2D7B2DDC35E566A FOREIGN KEY (family_id) REFERENCES family (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE family_invitation DROP FOREIGN KEY FK_C2D7B2DDC35E566A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE family_invitation
        SQL);
    }
}
