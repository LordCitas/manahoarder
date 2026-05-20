<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519224907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD COLUMN nickname VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD COLUMN profile_picture_filename VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD COLUMN profile_art_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_NICKNAME ON "user" (nickname)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_NICKNAME');
        $this->addSql('ALTER TABLE "user" DROP COLUMN profile_art_url');
        $this->addSql('ALTER TABLE "user" DROP COLUMN profile_picture_filename');
        $this->addSql('ALTER TABLE "user" DROP COLUMN nickname');
    }
}
