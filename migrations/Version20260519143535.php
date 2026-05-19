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
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_card AS SELECT id, quantity, is_foil, date_added, user_id, scryfall_card_id FROM user_card');
        $this->addSql('DROP TABLE user_card');
        $this->addSql('CREATE TABLE user_card (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantity INTEGER NOT NULL, is_foil BOOLEAN NOT NULL, date_added DATETIME NOT NULL, user_id INTEGER NOT NULL, scryfall_card_id INTEGER NOT NULL, album_id INTEGER NOT NULL, CONSTRAINT FK_6C95D41AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6C95D41A329D0CA FOREIGN KEY (scryfall_card_id) REFERENCES scryfall_card (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6C95D41A1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user_card (id, quantity, is_foil, date_added, user_id, scryfall_card_id) SELECT id, quantity, is_foil, date_added, user_id, scryfall_card_id FROM __temp__user_card');
        $this->addSql('DROP TABLE __temp__user_card');
        $this->addSql('CREATE INDEX IDX_6C95D41A329D0CA ON user_card (scryfall_card_id)');
        $this->addSql('CREATE INDEX IDX_6C95D41AA76ED395 ON user_card (user_id)');
        $this->addSql('CREATE INDEX IDX_6C95D41A1137ABCF ON user_card (album_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user_card AS SELECT id, quantity, is_foil, date_added, user_id, scryfall_card_id FROM user_card');
        $this->addSql('DROP TABLE user_card');
        $this->addSql('CREATE TABLE user_card (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantity INTEGER NOT NULL, is_foil BOOLEAN NOT NULL, date_added DATETIME NOT NULL, user_id INTEGER NOT NULL, scryfall_card_id INTEGER NOT NULL, CONSTRAINT FK_6C95D41AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6C95D41A329D0CA FOREIGN KEY (scryfall_card_id) REFERENCES scryfall_card (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user_card (id, quantity, is_foil, date_added, user_id, scryfall_card_id) SELECT id, quantity, is_foil, date_added, user_id, scryfall_card_id FROM __temp__user_card');
        $this->addSql('DROP TABLE __temp__user_card');
        $this->addSql('CREATE INDEX IDX_6C95D41AA76ED395 ON user_card (user_id)');
        $this->addSql('CREATE INDEX IDX_6C95D41A329D0CA ON user_card (scryfall_card_id)');
    }
}
