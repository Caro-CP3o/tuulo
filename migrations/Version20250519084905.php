<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519084905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql(<<<'SQL'
        //     ALTER TABLE family_member ADD status VARCHAR(20) NOT NULL
        // SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        SQL);
    }

    // public function down(Schema $schema): void
    // {
    //     // this down() migration is auto-generated, please modify it to your needs
    //     $this->addSql(<<<'SQL'
    //         ALTER TABLE user DROP created_at, DROP updated_at
    //     SQL);
    //     $this->addSql(<<<'SQL'
    //         ALTER TABLE family_member DROP status
    //     SQL);
    // }
}
