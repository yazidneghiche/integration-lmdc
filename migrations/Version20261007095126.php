<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261007095126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account ADD address VARCHAR(255) DEFAULT NULL, ADD address_complement VARCHAR(255) DEFAULT NULL, CHANGE siret siret VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD platform VARCHAR(20) DEFAULT \'b2c\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account DROP address, DROP address_complement, CHANGE siret siret VARCHAR(14) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` DROP platform');
    }
}
