<?php

declare(strict_types=1);

namespace EveSrp\Migrations\PostgreSQL;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * @noinspection PhpUnused
 */
final class Version20230506155129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            "Migration can only be executed safely on PostgreSQLPlatform."
        );

        $this->addSql('CREATE SEQUENCE actions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE divisions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE external_groups_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE modifiers_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE permissions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE actions (id INT NOT NULL, user_id INT NOT NULL, request_id BIGINT NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, category VARCHAR(16) NOT NULL, note TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_548F1EFA76ED395 ON actions (user_id)');
        $this->addSql('CREATE INDEX IDX_548F1EF427EB8A5 ON actions (request_id)');
        $this->addSql('CREATE TABLE characters (id BIGINT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, main BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3A29410EA76ED395 ON characters (user_id)');
        $this->addSql('CREATE INDEX characters_name_idx ON characters (name)');
        $this->addSql('CREATE TABLE divisions (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE esi_types (id BIGINT NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE external_groups (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_84EEA6995E237E06 ON external_groups (name)');
        $this->addSql('CREATE TABLE modifiers (id INT NOT NULL, request_id BIGINT NOT NULL, user_id INT NOT NULL, voided_user_id INT DEFAULT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, mod_type VARCHAR(8) NOT NULL, note TEXT DEFAULT NULL, voided_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, mod_value BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D8A9FE73427EB8A5 ON modifiers (request_id)');
        $this->addSql('CREATE INDEX IDX_D8A9FE73A76ED395 ON modifiers (user_id)');
        $this->addSql('CREATE INDEX IDX_D8A9FE733F26CF61 ON modifiers (voided_user_id)');
        $this->addSql('CREATE TABLE permissions (id INT NOT NULL, division_id INT NOT NULL, external_group_id INT NOT NULL, role_name VARCHAR(8) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2DEDCC6F41859289 ON permissions (division_id)');
        $this->addSql('CREATE INDEX IDX_2DEDCC6F11586BB4 ON permissions (external_group_id)');
        $this->addSql('CREATE TABLE requests (id BIGINT NOT NULL, division_id INT DEFAULT NULL, user_id INT NOT NULL, last_editor INT DEFAULT NULL, character_id BIGINT NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, corporation_id BIGINT DEFAULT NULL, corporation_name VARCHAR(255) DEFAULT NULL, alliance_id BIGINT DEFAULT NULL, alliance_name VARCHAR(255) DEFAULT NULL, ship VARCHAR(128) NOT NULL, kill_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, solar_system VARCHAR(32) NOT NULL, esi_hash VARCHAR(512) DEFAULT NULL, details TEXT DEFAULT NULL, killMail TEXT DEFAULT NULL, base_payout BIGINT DEFAULT NULL, payout BIGINT DEFAULT NULL, status VARCHAR(16) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7B85D65141859289 ON requests (division_id)');
        $this->addSql('CREATE INDEX IDX_7B85D651A76ED395 ON requests (user_id)');
        $this->addSql('CREATE INDEX IDX_7B85D65146F67360 ON requests (last_editor)');
        $this->addSql('CREATE INDEX IDX_7B85D6511136BE75 ON requests (character_id)');
        $this->addSql('CREATE INDEX requests_status_idx ON requests (status)');
        $this->addSql('CREATE INDEX requests_corporation_name_idx ON requests (corporation_name)');
        $this->addSql('CREATE INDEX requests_ship_idx ON requests (ship)');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, external_account_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E98195D068 ON users (external_account_id)');
        $this->addSql('CREATE INDEX users_name_idx ON users (name)');
        $this->addSql('CREATE TABLE user_external_group (user_id INT NOT NULL, external_group_id INT NOT NULL, PRIMARY KEY(user_id, external_group_id))');
        $this->addSql('CREATE INDEX IDX_3408E81FA76ED395 ON user_external_group (user_id)');
        $this->addSql('CREATE INDEX IDX_3408E81F11586BB4 ON user_external_group (external_group_id)');
        $this->addSql('ALTER TABLE actions ADD CONSTRAINT FK_548F1EFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE actions ADD CONSTRAINT FK_548F1EF427EB8A5 FOREIGN KEY (request_id) REFERENCES requests (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE characters ADD CONSTRAINT FK_3A29410EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE modifiers ADD CONSTRAINT FK_D8A9FE73427EB8A5 FOREIGN KEY (request_id) REFERENCES requests (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE modifiers ADD CONSTRAINT FK_D8A9FE73A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE modifiers ADD CONSTRAINT FK_D8A9FE733F26CF61 FOREIGN KEY (voided_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE permissions ADD CONSTRAINT FK_2DEDCC6F41859289 FOREIGN KEY (division_id) REFERENCES divisions (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE permissions ADD CONSTRAINT FK_2DEDCC6F11586BB4 FOREIGN KEY (external_group_id) REFERENCES external_groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE requests ADD CONSTRAINT FK_7B85D65141859289 FOREIGN KEY (division_id) REFERENCES divisions (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE requests ADD CONSTRAINT FK_7B85D651A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE requests ADD CONSTRAINT FK_7B85D65146F67360 FOREIGN KEY (last_editor) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE requests ADD CONSTRAINT FK_7B85D6511136BE75 FOREIGN KEY (character_id) REFERENCES characters (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_external_group ADD CONSTRAINT FK_3408E81FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_external_group ADD CONSTRAINT FK_3408E81F11586BB4 FOREIGN KEY (external_group_id) REFERENCES external_groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE actions_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE divisions_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE external_groups_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE modifiers_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE permissions_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE actions DROP CONSTRAINT FK_548F1EFA76ED395');
        $this->addSql('ALTER TABLE actions DROP CONSTRAINT FK_548F1EF427EB8A5');
        $this->addSql('ALTER TABLE characters DROP CONSTRAINT FK_3A29410EA76ED395');
        $this->addSql('ALTER TABLE modifiers DROP CONSTRAINT FK_D8A9FE73427EB8A5');
        $this->addSql('ALTER TABLE modifiers DROP CONSTRAINT FK_D8A9FE73A76ED395');
        $this->addSql('ALTER TABLE modifiers DROP CONSTRAINT FK_D8A9FE733F26CF61');
        $this->addSql('ALTER TABLE permissions DROP CONSTRAINT FK_2DEDCC6F41859289');
        $this->addSql('ALTER TABLE permissions DROP CONSTRAINT FK_2DEDCC6F11586BB4');
        $this->addSql('ALTER TABLE requests DROP CONSTRAINT FK_7B85D65141859289');
        $this->addSql('ALTER TABLE requests DROP CONSTRAINT FK_7B85D651A76ED395');
        $this->addSql('ALTER TABLE requests DROP CONSTRAINT FK_7B85D65146F67360');
        $this->addSql('ALTER TABLE requests DROP CONSTRAINT FK_7B85D6511136BE75');
        $this->addSql('ALTER TABLE user_external_group DROP CONSTRAINT FK_3408E81FA76ED395');
        $this->addSql('ALTER TABLE user_external_group DROP CONSTRAINT FK_3408E81F11586BB4');
        $this->addSql('DROP TABLE actions');
        $this->addSql('DROP TABLE characters');
        $this->addSql('DROP TABLE divisions');
        $this->addSql('DROP TABLE esi_types');
        $this->addSql('DROP TABLE external_groups');
        $this->addSql('DROP TABLE modifiers');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE requests');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE user_external_group');
    }
}
