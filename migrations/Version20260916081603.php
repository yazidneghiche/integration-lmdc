<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916081603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée le schéma initial de la passerelle LMDC.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('account')) {
            $this->addSql("CREATE TABLE account (
                id INT AUTO_INCREMENT NOT NULL,
                company VARCHAR(255) NOT NULL,
                firstname VARCHAR(255) DEFAULT NULL,
                lastname VARCHAR(255) DEFAULT NULL,
                phone VARCHAR(255) DEFAULT NULL,
                address VARCHAR(255) DEFAULT NULL,
                address_complement VARCHAR(255) DEFAULT NULL,
                vat VARCHAR(255) DEFAULT NULL,
                siret VARCHAR(32) DEFAULT NULL,
                zipcode VARCHAR(255) NOT NULL,
                city VARCHAR(255) NOT NULL,
                country VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                ct_number VARCHAR(255) DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                is_new TINYINT(1) DEFAULT NULL,
                validate TINYINT(1) DEFAULT NULL,
                source_customer_id INT DEFAULT NULL,
                sync TINYINT(1) DEFAULT NULL,
                notification_sent_at DATETIME DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_ACCOUNT_EMAIL (email),
                INDEX IDX_ACCOUNT_CT_NUMBER (ct_number)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        } elseif (!$schema->getTable('account')->hasColumn('notification_sent_at')) {
            $this->addSql('ALTER TABLE account ADD notification_sent_at DATETIME DEFAULT NULL');
        }

        if (!$schema->hasTable('order')) {
            $this->addSql("CREATE TABLE `order` (
                id INT AUTO_INCREMENT NOT NULL,
                account_id INT DEFAULT NULL,
                company VARCHAR(255) DEFAULT NULL,
                ct_number VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                csvfile VARCHAR(255) DEFAULT NULL,
                total DOUBLE PRECISION DEFAULT NULL,
                status INT DEFAULT NULL,
                last_update DATETIME DEFAULT NULL,
                sync TINYINT(1) NOT NULL,
                sage_total_ht DOUBLE PRECISION DEFAULT NULL,
                sage_total_ttc DOUBLE PRECISION DEFAULT NULL,
                sage_type INT DEFAULT NULL,
                sage_status INT DEFAULT NULL,
                sage_piece VARCHAR(255) DEFAULT NULL,
                sage_net_apayer DOUBLE PRECISION DEFAULT NULL,
                source VARCHAR(100) NOT NULL,
                platform VARCHAR(20) NOT NULL DEFAULT 'b2c',
                source_order_id VARCHAR(100) NOT NULL,
                total_ht DOUBLE PRECISION DEFAULT NULL,
                total_ttc DOUBLE PRECISION DEFAULT NULL,
                email VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_ORDER_ACCOUNT (account_id),
                INDEX IDX_ORDER_SOURCE (source, source_order_id),
                CONSTRAINT FK_ORDER_ACCOUNT FOREIGN KEY (account_id) REFERENCES account (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        }

        if (!$schema->hasTable('order_product')) {
            $this->addSql("CREATE TABLE order_product (
                id INT AUTO_INCREMENT NOT NULL,
                order_id INT NOT NULL,
                reference VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                price DOUBLE PRECISION NOT NULL,
                quantity INT NOT NULL,
                unit_price DOUBLE PRECISION DEFAULT NULL,
                source_item_id VARCHAR(100) NOT NULL,
                PRIMARY KEY(id),
                INDEX IDX_ORDER_PRODUCT_ORDER (order_id),
                CONSTRAINT FK_ORDER_PRODUCT_ORDER FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS order_product');
        $this->addSql('DROP TABLE IF EXISTS `order`');
        $this->addSql('DROP TABLE IF EXISTS account');
    }
}
