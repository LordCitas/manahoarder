<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519223104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE decklist (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, format VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_ED030EC6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_ED030EC6A76ED395 ON decklist (user_id)');
        $this->addSql('CREATE TABLE tournament (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, format VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, max_players INTEGER NOT NULL, invite_code VARCHAR(8) NOT NULL, created_at DATETIME NOT NULL, start_date DATETIME DEFAULT NULL, current_round INTEGER DEFAULT NULL, creator_id INTEGER NOT NULL, CONSTRAINT FK_BD5FB8D961220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BD5FB8D961220EA6 ON tournament (creator_id)');
        $this->addSql('CREATE TABLE tournament_participant (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, joined_at DATETIME NOT NULL, wins INTEGER NOT NULL, losses INTEGER NOT NULL, position INTEGER DEFAULT NULL, tournament_id INTEGER NOT NULL, user_id INTEGER NOT NULL, decklist_id INTEGER DEFAULT NULL, CONSTRAINT FK_5C4BB35B33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5C4BB35BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5C4BB35BF4E9531B FOREIGN KEY (decklist_id) REFERENCES decklist (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5C4BB35B33D1A3E7 ON tournament_participant (tournament_id)');
        $this->addSql('CREATE INDEX IDX_5C4BB35BA76ED395 ON tournament_participant (user_id)');
        $this->addSql('CREATE INDEX IDX_5C4BB35BF4E9531B ON tournament_participant (decklist_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE decklist');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('DROP TABLE tournament_participant');
    }
}
