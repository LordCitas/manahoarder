<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519143535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_card ADD COLUMN album_id INTEGER NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE user_card ADD CONSTRAINT FK_6C95D41A1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6C95D41A1137ABCF ON user_card (album_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_6C95D41A1137ABCF');
        $this->addSql('ALTER TABLE user_card DROP CONSTRAINT FK_6C95D41A1137ABCF');
        $this->addSql('ALTER TABLE user_card DROP COLUMN album_id');
    }
}
