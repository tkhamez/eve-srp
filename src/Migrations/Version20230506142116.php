<?php

declare(strict_types=1);

namespace EveSrp\Migrations;

use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * @noinspection PhpUnused
 */
final class Version20230506142116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MariaDBPlatform &&
            !$this->connection->getDatabasePlatform() instanceof MySQL80Platform,
            "Migration can only be executed safely on MariaDBPlatform|MySQL80Platform."
        );

        $this->addSql('CREATE TABLE permissions (id INT AUTO_INCREMENT NOT NULL, division_id INT NOT NULL, external_group_id INT NOT NULL, role_name VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_520_ci`, INDEX IDX_2DEDCC6F41859289 (division_id), INDEX IDX_2DEDCC6F11586BB4 (external_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE requests (id BIGINT NOT NULL, division_id INT DEFAULT NULL, user_id INT NOT NULL, character_id BIGINT NOT NULL, last_editor INT DEFAULT NULL, created DATETIME NOT NULL, corporation_id BIGINT DEFAULT NULL, corporation_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, alliance_id BIGINT DEFAULT NULL, alliance_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, ship VARCHAR(128) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_520_ci`, kill_time DATETIME NOT NULL, solar_system VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_520_ci`, esi_hash VARCHAR(512) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, details MEDIUMTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, base_payout BIGINT DEFAULT NULL, payout BIGINT DEFAULT NULL, status VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_520_ci`, killMail TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, INDEX requests_ship_idx (ship), INDEX IDX_7B85D65146F67360 (last_editor), INDEX IDX_7B85D65141859289 (division_id), INDEX requests_status_idx (status), INDEX IDX_7B85D651A76ED395 (user_id), INDEX requests_corporation_name_idx (corporation_name), INDEX IDX_7B85D6511136BE75 (character_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE modifiers (id INT AUTO_INCREMENT NOT NULL, request_id BIGINT NOT NULL, user_id INT NOT NULL, voided_user_id INT DEFAULT NULL, created DATETIME NOT NULL, mod_type VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_520_ci`, note MEDIUMTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, voided_time DATETIME DEFAULT NULL, mod_value BIGINT NOT NULL, INDEX IDX_D8A9FE73A76ED395 (user_id), INDEX IDX_D8A9FE733F26CF61 (voided_user_id), INDEX IDX_D8A9FE73427EB8A5 (request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE actions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, request_id BIGINT NOT NULL, created DATETIME NOT NULL, category VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_520_ci`, note MEDIUMTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, INDEX IDX_548F1EFA76ED395 (user_id), INDEX IDX_548F1EF427EB8A5 (request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE esi_types (id BIGINT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_external_group (user_id INT NOT NULL, external_group_id INT NOT NULL, INDEX IDX_3408E81FA76ED395 (user_id), INDEX IDX_3408E81F11586BB4 (external_group_id), PRIMARY KEY(user_id, external_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE characters (id BIGINT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_520_ci`, main TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_3A29410EA76ED395 (user_id), INDEX characters_name_idx (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE divisions (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_520_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, external_account_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_520_ci`, INDEX users_name_idx (name), UNIQUE INDEX UNIQ_1483A5E98195D068 (external_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE external_groups (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_520_ci`, UNIQUE INDEX UNIQ_84EEA6995E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_520_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE requests');
        $this->addSql('DROP TABLE modifiers');
        $this->addSql('DROP TABLE actions');
        $this->addSql('DROP TABLE esi_types');
        $this->addSql('DROP TABLE user_external_group');
        $this->addSql('DROP TABLE characters');
        $this->addSql('DROP TABLE divisions');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE external_groups');
    }
}
