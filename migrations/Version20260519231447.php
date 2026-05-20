<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519231447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE scryfall_card ADD COLUMN art_crop_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__scryfall_card AS SELECT id, scryfall_id, name, mana_cost, image_url, type, card_text, card_set, created_at FROM scryfall_card');
        $this->addSql('DROP TABLE scryfall_card');
        $this->addSql('CREATE TABLE scryfall_card (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, scryfall_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, mana_cost VARCHAR(255) NOT NULL, image_url VARCHAR(500) NOT NULL, type VARCHAR(255) DEFAULT NULL, card_text CLOB DEFAULT NULL, card_set VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO scryfall_card (id, scryfall_id, name, mana_cost, image_url, type, card_text, card_set, created_at) SELECT id, scryfall_id, name, mana_cost, image_url, type, card_text, card_set, created_at FROM __temp__scryfall_card');
        $this->addSql('DROP TABLE __temp__scryfall_card');
    }
}
