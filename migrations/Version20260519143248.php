<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519143248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE album (id SERIAL PRIMARY KEY NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_39986E43A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_39986E43A76ED395 ON album (user_id)');
        $this->addSql('CREATE TABLE card (id SERIAL PRIMARY KEY NOT NULL)');
        $this->addSql('CREATE TABLE scryfall_card (id SERIAL PRIMARY KEY NOT NULL, scryfall_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, mana_cost VARCHAR(255) NOT NULL, image_url VARCHAR(500) NOT NULL, type VARCHAR(255) DEFAULT NULL, card_text TEXT DEFAULT NULL, card_set VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP NOT NULL)');
        $this->addSql('CREATE TABLE user_card (id SERIAL PRIMARY KEY NOT NULL, quantity INTEGER NOT NULL, is_foil BOOLEAN NOT NULL, date_added TIMESTAMP NOT NULL, user_id INTEGER NOT NULL, scryfall_card_id INTEGER NOT NULL, CONSTRAINT FK_6C95D41AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6C95D41A329D0CA FOREIGN KEY (scryfall_card_id) REFERENCES scryfall_card (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6C95D41AA76ED395 ON user_card (user_id)');
        $this->addSql('CREATE INDEX IDX_6C95D41A329D0CA ON user_card (scryfall_card_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE album');
        $this->addSql('DROP TABLE card');
        $this->addSql('DROP TABLE scryfall_card');
        $this->addSql('DROP TABLE user_card');
    }
}
