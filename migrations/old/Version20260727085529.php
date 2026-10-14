<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260727085529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ps_order_detail DROP FOREIGN KEY `fk_order_detail_order`');
        $this->addSql('DROP TABLE ps_order_detail');
        $this->addSql('DROP TABLE ps_orders');
        $this->addSql('ALTER TABLE order_product ADD name VARCHAR(255) NOT NULL, ADD source_item_id VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ps_order_detail (id_order_detail INT UNSIGNED AUTO_INCREMENT NOT NULL, id_order INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, product_attribute_id INT UNSIGNED DEFAULT 0 NOT NULL, product_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, product_reference VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, product_quantity INT UNSIGNED DEFAULT 0 NOT NULL, unit_price_tax_excl NUMERIC(20, 6) DEFAULT \'0.000000\' NOT NULL, unit_price_tax_incl NUMERIC(20, 6) DEFAULT \'0.000000\' NOT NULL, total_price_tax_excl NUMERIC(20, 6) DEFAULT \'0.000000\' NOT NULL, total_price_tax_incl NUMERIC(20, 6) DEFAULT \'0.000000\' NOT NULL, INDEX id_order (id_order), PRIMARY KEY (id_order_detail)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ps_orders (id_order INT UNSIGNED AUTO_INCREMENT NOT NULL, reference VARCHAR(9) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, id_shop_group INT UNSIGNED DEFAULT 1 NOT NULL, id_shop INT UNSIGNED DEFAULT 1 NOT NULL, id_carrier INT UNSIGNED DEFAULT 0 NOT NULL, id_lang INT UNSIGNED DEFAULT 1 NOT NULL, id_customer INT UNSIGNED DEFAULT 0 NOT NULL, id_address_delivery INT UNSIGNED DEFAULT 0 NOT NULL, id_address_invoice INT UNSIGNED DEFAULT 0 NOT NULL, current_state INT UNSIGNED DEFAULT 1 NOT NULL, module VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, payment VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, total_paid NUMERIC(20, 6) DEFAULT \'0.000000\' NOT NULL, total_paid_tax_incl NUMERIC(20, 6) DEFAULT \'0.000000\' NOT NULL, total_paid_tax_excl NUMERIC(20, 6) DEFAULT \'0.000000\' NOT NULL, total_products NUMERIC(20, 6) DEFAULT \'0.000000\' NOT NULL, total_products_wt NUMERIC(20, 6) DEFAULT \'0.000000\' NOT NULL, invoice_date DATETIME DEFAULT NULL, delivery_date DATETIME DEFAULT NULL, valid TINYINT DEFAULT 1 NOT NULL, date_add DATETIME NOT NULL, date_upd DATETIME NOT NULL, INDEX date_upd (date_upd), INDEX reference (reference), PRIMARY KEY (id_order)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE ps_order_detail ADD CONSTRAINT `fk_order_detail_order` FOREIGN KEY (id_order) REFERENCES ps_orders (id_order) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_product DROP name, DROP source_item_id');
    }
}
