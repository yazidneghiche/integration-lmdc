-- Mock Prestashop 1.7
DROP DATABASE IF EXISTS `ps_prestashop17`;
CREATE DATABASE `ps_prestashop17` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ps_prestashop17`;

CREATE TABLE `ps_orders` (
  `id_order` int unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(9) NOT NULL,
  `id_shop_group` int unsigned NOT NULL DEFAULT 1,
  `id_shop` int unsigned NOT NULL DEFAULT 1,
  `id_carrier` int unsigned NOT NULL DEFAULT 0,
  `id_lang` int unsigned NOT NULL DEFAULT 1,
  `id_customer` int unsigned NOT NULL DEFAULT 0,
  `id_address_delivery` int unsigned NOT NULL DEFAULT 0,
  `id_address_invoice` int unsigned NOT NULL DEFAULT 0,
  `current_state` int unsigned NOT NULL DEFAULT 1,
  `module` varchar(255) DEFAULT NULL,
  `payment` varchar(255) DEFAULT NULL,
  `total_paid` decimal(20,6) NOT NULL DEFAULT 0,
  `total_paid_tax_incl` decimal(20,6) NOT NULL DEFAULT 0,
  `total_paid_tax_excl` decimal(20,6) NOT NULL DEFAULT 0,
  `total_products` decimal(20,6) NOT NULL DEFAULT 0,
  `total_products_wt` decimal(20,6) NOT NULL DEFAULT 0,
  `invoice_date` datetime DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `valid` tinyint(1) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL,
  `date_upd` datetime NOT NULL,
  PRIMARY KEY (`id_order`),
  KEY `reference` (`reference`),
  KEY `date_upd` (`date_upd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ps_order_detail` (
  `id_order_detail` int unsigned NOT NULL AUTO_INCREMENT,
  `id_order` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `product_attribute_id` int unsigned NOT NULL DEFAULT 0,
  `product_name` varchar(255) NOT NULL,
  `product_reference` varchar(64) DEFAULT NULL,
  `product_quantity` int unsigned NOT NULL DEFAULT 0,
  `unit_price_tax_excl` decimal(20,6) NOT NULL DEFAULT 0,
  `unit_price_tax_incl` decimal(20,6) NOT NULL DEFAULT 0,
  `total_price_tax_excl` decimal(20,6) NOT NULL DEFAULT 0,
  `total_price_tax_incl` decimal(20,6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_order_detail`),
  KEY `id_order` (`id_order`),
  CONSTRAINT `fk_order_detail_order` FOREIGN KEY (`id_order`)
    REFERENCES `ps_orders`(`id_order`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ps_orders
(reference,id_customer,current_state,module,payment,total_paid,total_paid_tax_incl,total_paid_tax_excl,total_products,total_products_wt,invoice_date,delivery_date,valid,date_add,date_upd)
VALUES
('ABCD00001',1,2,'ps_checkpayment','Cheque',129.90,129.90,108.25,108.25,129.90,NOW(),NOW(),1,NOW(),NOW()),
('ABCD00002',2,3,'ps_wirepayment','Wire',245.00,245.00,204.17,204.17,245.00,NOW(),NOW(),1,NOW(),NOW()),
('ABCD00003',3,5,'ps_wirepayment','Card',89.90,89.90,74.92,74.92,89.90,NOW(),NOW(),1,NOW(),NOW());

INSERT INTO ps_order_detail
(id_order,product_id,product_name,product_reference,product_quantity,unit_price_tax_excl,unit_price_tax_incl,total_price_tax_excl,total_price_tax_incl)
VALUES
(1,10,'Crème visage','678888-2',2,25.00,30.00,50.00,60.00),
(1,11,'Sérum','678888-3',1,40.21,48.25,40.21,48.25),
(2,12,'Masque','712345-1',3,20.00,24.00,60.00,72.00),
(2,13,'Gel nettoyant','712345-2',2,36.08,43.30,72.16,86.60),
(3,14,'Lotion','998877-1',1,74.92,89.90,74.92,89.90);
