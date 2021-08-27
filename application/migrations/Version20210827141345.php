<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210827141345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_1150D5671775DC57');
        $this->addSql('CREATE TEMPORARY TABLE __temp__attendee AS SELECT id, meeting_id_id, user_id, role, full_name, is_listening_only, has_joined_voice, has_video, client_type, custom_data, is_presenter FROM attendee');
        $this->addSql('DROP TABLE attendee');
        $this->addSql('CREATE TABLE attendee (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, meeting_id_id INTEGER NOT NULL, user_id VARCHAR(255) NOT NULL COLLATE BINARY, role VARCHAR(255) NOT NULL COLLATE BINARY, full_name VARCHAR(255) NOT NULL COLLATE BINARY, is_listening_only BOOLEAN NOT NULL, has_joined_voice BOOLEAN NOT NULL, has_video BOOLEAN NOT NULL, client_type VARCHAR(255) NOT NULL COLLATE BINARY, custom_data CLOB NOT NULL COLLATE BINARY --(DC2Type:json)
        , is_presenter BOOLEAN NOT NULL, server_id VARCHAR(255) NOT NULL, CONSTRAINT FK_1150D5671775DC57 FOREIGN KEY (meeting_id_id) REFERENCES meeting (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO attendee (id, meeting_id_id, user_id, role, full_name, is_listening_only, has_joined_voice, has_video, client_type, custom_data, is_presenter) SELECT id, meeting_id_id, user_id, role, full_name, is_listening_only, has_joined_voice, has_video, client_type, custom_data, is_presenter FROM __temp__attendee');
        $this->addSql('DROP TABLE __temp__attendee');
        $this->addSql('CREATE INDEX IDX_1150D5671775DC57 ON attendee (meeting_id_id)');
        $this->addSql('ALTER TABLE meeting ADD COLUMN server_id VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX IDX_BB532B5367433D9C');
        $this->addSql('DROP INDEX UNIQ_BB532B534DFD750C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__recording AS SELECT id, meeting_id, record_id, published, protected, is_breakout, start_time, end_time, participants, metadata, playback, headless, imported, recording, broker_notified FROM recording');
        $this->addSql('DROP TABLE recording');
        $this->addSql('CREATE TABLE recording (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, meeting_id INTEGER NOT NULL, record_id VARCHAR(255) NOT NULL COLLATE BINARY, published BOOLEAN NOT NULL, protected BOOLEAN NOT NULL, is_breakout BOOLEAN NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, participants INTEGER NOT NULL, metadata CLOB NOT NULL COLLATE BINARY --(DC2Type:json)
        , playback CLOB NOT NULL COLLATE BINARY --(DC2Type:json)
        , headless BOOLEAN NOT NULL, imported BOOLEAN NOT NULL, recording CLOB NOT NULL COLLATE BINARY --(DC2Type:json)
        , broker_notified BOOLEAN NOT NULL, server_id VARCHAR(255) NOT NULL, CONSTRAINT FK_BB532B5367433D9C FOREIGN KEY (meeting_id) REFERENCES meeting (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO recording (id, meeting_id, record_id, published, protected, is_breakout, start_time, end_time, participants, metadata, playback, headless, imported, recording, broker_notified) SELECT id, meeting_id, record_id, published, protected, is_breakout, start_time, end_time, participants, metadata, playback, headless, imported, recording, broker_notified FROM __temp__recording');
        $this->addSql('DROP TABLE __temp__recording');
        $this->addSql('CREATE INDEX IDX_BB532B5367433D9C ON recording (meeting_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BB532B534DFD750C ON recording (record_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_1150D5671775DC57');
        $this->addSql('CREATE TEMPORARY TABLE __temp__attendee AS SELECT id, meeting_id_id, user_id, role, full_name, is_listening_only, has_joined_voice, has_video, client_type, custom_data, is_presenter FROM attendee');
        $this->addSql('DROP TABLE attendee');
        $this->addSql('CREATE TABLE attendee (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, meeting_id_id INTEGER NOT NULL, user_id VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, full_name VARCHAR(255) NOT NULL, is_listening_only BOOLEAN NOT NULL, has_joined_voice BOOLEAN NOT NULL, has_video BOOLEAN NOT NULL, client_type VARCHAR(255) NOT NULL, custom_data CLOB NOT NULL --(DC2Type:json)
        , is_presenter BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO attendee (id, meeting_id_id, user_id, role, full_name, is_listening_only, has_joined_voice, has_video, client_type, custom_data, is_presenter) SELECT id, meeting_id_id, user_id, role, full_name, is_listening_only, has_joined_voice, has_video, client_type, custom_data, is_presenter FROM __temp__attendee');
        $this->addSql('DROP TABLE __temp__attendee');
        $this->addSql('CREATE INDEX IDX_1150D5671775DC57 ON attendee (meeting_id_id)');
        $this->addSql('DROP INDEX UNIQ_F515E13967433D9C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__meeting AS SELECT id, meeting_id, attendee_pw, moderator_pw, voice_bridge, dial_number, meeting_name, metadata, create_time, start_time, end_time, has_user_joined, has_been_forcibly_ended, recording, max_users, running FROM meeting');
        $this->addSql('DROP TABLE meeting');
        $this->addSql('CREATE TABLE meeting (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, meeting_id VARCHAR(255) NOT NULL, attendee_pw VARCHAR(255) NOT NULL, moderator_pw VARCHAR(255) NOT NULL, voice_bridge INTEGER NOT NULL, dial_number INTEGER NOT NULL, meeting_name VARCHAR(255) NOT NULL, metadata CLOB NOT NULL --(DC2Type:json)
        , create_time DATETIME NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, has_user_joined BOOLEAN NOT NULL, has_been_forcibly_ended BOOLEAN NOT NULL, recording BOOLEAN NOT NULL, max_users INTEGER NOT NULL, running BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO meeting (id, meeting_id, attendee_pw, moderator_pw, voice_bridge, dial_number, meeting_name, metadata, create_time, start_time, end_time, has_user_joined, has_been_forcibly_ended, recording, max_users, running) SELECT id, meeting_id, attendee_pw, moderator_pw, voice_bridge, dial_number, meeting_name, metadata, create_time, start_time, end_time, has_user_joined, has_been_forcibly_ended, recording, max_users, running FROM __temp__meeting');
        $this->addSql('DROP TABLE __temp__meeting');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F515E13967433D9C ON meeting (meeting_id)');
        $this->addSql('DROP INDEX UNIQ_BB532B534DFD750C');
        $this->addSql('DROP INDEX IDX_BB532B5367433D9C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__recording AS SELECT id, meeting_id, record_id, published, protected, is_breakout, start_time, end_time, participants, metadata, playback, headless, imported, recording, broker_notified FROM recording');
        $this->addSql('DROP TABLE recording');
        $this->addSql('CREATE TABLE recording (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, meeting_id INTEGER NOT NULL, record_id VARCHAR(255) NOT NULL, published BOOLEAN NOT NULL, protected BOOLEAN NOT NULL, is_breakout BOOLEAN NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, participants INTEGER NOT NULL, metadata CLOB NOT NULL --(DC2Type:json)
        , playback CLOB NOT NULL --(DC2Type:json)
        , headless BOOLEAN NOT NULL, imported BOOLEAN NOT NULL, recording CLOB NOT NULL --(DC2Type:json)
        , broker_notified BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO recording (id, meeting_id, record_id, published, protected, is_breakout, start_time, end_time, participants, metadata, playback, headless, imported, recording, broker_notified) SELECT id, meeting_id, record_id, published, protected, is_breakout, start_time, end_time, participants, metadata, playback, headless, imported, recording, broker_notified FROM __temp__recording');
        $this->addSql('DROP TABLE __temp__recording');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BB532B534DFD750C ON recording (record_id)');
        $this->addSql('CREATE INDEX IDX_BB532B5367433D9C ON recording (meeting_id)');
    }
}
