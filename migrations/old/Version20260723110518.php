<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260723110518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, company VARCHAR(255) DEFAULT NULL, ct_number VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, csvfile VARCHAR(255) DEFAULT NULL, total DOUBLE PRECISION DEFAULT NULL, status INT DEFAULT NULL, last_update VARCHAR(255) DEFAULT NULL, sync TINYINT NOT NULL, sage_total_ht DOUBLE PRECISION DEFAULT NULL, sage_total_ttc DOUBLE PRECISION DEFAULT NULL, sage_type INT DEFAULT NULL, sage_status INT DEFAULT NULL, sage_piece VARCHAR(255) DEFAULT NULL, sage_net_apayer DOUBLE PRECISION DEFAULT NULL, source VARCHAR(100) NOT NULL, source_order_id VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_product (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, quantity INT NOT NULL, unit_price DOUBLE PRECISION DEFAULT NULL, order_id INT NOT NULL, INDEX IDX_2530ADE68D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE order_product ADD CONSTRAINT FK_2530ADE68D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_product DROP FOREIGN KEY FK_2530ADE68D9F6D38');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_product');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
