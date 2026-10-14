<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260910140237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` ADD email VARCHAR(255) DEFAULT NULL, ADD account_id INT NOT NULL, CHANGE ct_number ct_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993989B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('CREATE INDEX IDX_F52993989B6B5FBA ON `order` (account_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989B6B5FBA');
        $this->addSql('DROP INDEX IDX_F52993989B6B5FBA ON `order`');
        $this->addSql('ALTER TABLE `order` DROP email, DROP account_id, CHANGE ct_number ct_number VARCHAR(255) NOT NULL');
    }
}
