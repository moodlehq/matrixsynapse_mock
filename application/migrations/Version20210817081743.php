<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210817081743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attendee (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, meeting_id_id INTEGER NOT NULL, user_id VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, full_name VARCHAR(255) NOT NULL, is_listening_only BOOLEAN NOT NULL, has_joined_voice BOOLEAN NOT NULL, has_video BOOLEAN NOT NULL, client_type VARCHAR(255) NOT NULL, custom_data CLOB NOT NULL --(DC2Type:json)
        , is_presenter BOOLEAN NOT NULL)');
        $this->addSql('CREATE INDEX IDX_1150D5671775DC57 ON attendee (meeting_id_id)');
        $this->addSql('CREATE TABLE meeting (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, meeting_id VARCHAR(255) NOT NULL, attendee_pw VARCHAR(255) NOT NULL, moderator_pw VARCHAR(255) NOT NULL, voice_bridge INTEGER NOT NULL, dial_number INTEGER NOT NULL, meeting_name VARCHAR(255) NOT NULL, metadata CLOB NOT NULL --(DC2Type:json)
        , create_time DATETIME NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, has_user_joined BOOLEAN NOT NULL, has_been_forcibly_ended BOOLEAN NOT NULL, recording BOOLEAN NOT NULL, max_users INTEGER NOT NULL, running BOOLEAN NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F515E13967433D9C ON meeting (meeting_id)');
        $this->addSql('CREATE TABLE recording (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, meeting_id INTEGER NOT NULL, record_id VARCHAR(255) NOT NULL, published BOOLEAN NOT NULL, protected BOOLEAN NOT NULL, is_breakout BOOLEAN NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, participants INTEGER NOT NULL, metadata CLOB NOT NULL --(DC2Type:json)
        , playback CLOB NOT NULL --(DC2Type:json)
        , headless BOOLEAN NOT NULL, imported BOOLEAN NOT NULL, recording CLOB NOT NULL --(DC2Type:json)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BB532B534DFD750C ON recording (record_id)');
        $this->addSql('CREATE INDEX IDX_BB532B5367433D9C ON recording (meeting_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE attendee');
        $this->addSql('DROP TABLE meeting');
        $this->addSql('DROP TABLE recording');
    }
}
