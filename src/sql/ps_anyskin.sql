-- Base de test minimale inspirée de PrestaShop
-- Base cible : ps_anyskin
-- Tables : ps_customer, ps_orders, ps_order_detail

CREATE DATABASE IF NOT EXISTS `ps_anyskin`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `ps_anyskin`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `ps_order_detail`;
DROP TABLE IF EXISTS `ps_orders`;
DROP TABLE IF EXISTS `ps_customer`;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- CLIENTS
-- Structure minimale inspirée de la table customer PrestaShop
-- ct_number est un champ métier ajouté pour la passerelle Sage.
-- =========================================================
CREATE TABLE `ps_customer` (
                               `id_customer` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                               `company` VARCHAR(255) DEFAULT NULL,
                               `firstname` VARCHAR(255) NOT NULL,
                               `lastname` VARCHAR(255) NOT NULL,
                               `email` VARCHAR(255) NOT NULL,
                               `ct_number` VARCHAR(100) DEFAULT NULL,
                               `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
                               `date_add` DATETIME NOT NULL,
                               `date_upd` DATETIME NOT NULL,

                               PRIMARY KEY (`id_customer`),
                               KEY `customer_email` (`email`),
                               KEY `customer_ct_number` (`ct_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- COMMANDES
-- Sous-ensemble des vrais champs ps_orders utiles au projet.
-- =========================================================
CREATE TABLE `ps_orders` (
                             `id_order` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                             `reference` VARCHAR(255) DEFAULT NULL,
                             `id_customer` INT(10) UNSIGNED NOT NULL,
                             `current_state` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                             `total_paid` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
                             `total_paid_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
                             `total_paid_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
                             `total_products` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
                             `total_products_wt` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
                             `date_add` DATETIME NOT NULL,
                             `date_upd` DATETIME NOT NULL,

                             PRIMARY KEY (`id_order`),
                             KEY `reference` (`reference`),
                             KEY `id_customer` (`id_customer`),
                             KEY `current_state` (`current_state`),
                             KEY `date_add` (`date_add`),
                             KEY `date_upd` (`date_upd`),

                             CONSTRAINT `fk_ps_orders_customer`
                                 FOREIGN KEY (`id_customer`)
                                     REFERENCES `ps_customer` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- LIGNES DE COMMANDE
-- Sous-ensemble des vrais champs ps_order_detail utiles au projet.
-- =========================================================
CREATE TABLE `ps_order_detail` (
                                   `id_order_detail` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                   `id_order` INT(10) UNSIGNED NOT NULL,
                                   `product_id` INT(10) UNSIGNED NOT NULL,
                                   `product_name` MEDIUMTEXT NOT NULL,
                                   `product_quantity` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                                   `product_price` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
                                   `product_reference` VARCHAR(64) DEFAULT NULL,
                                   `total_price_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
                                   `total_price_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
                                   `unit_price_tax_incl` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
                                   `unit_price_tax_excl` DECIMAL(20,6) NOT NULL DEFAULT 0.000000,

                                   PRIMARY KEY (`id_order_detail`),
                                   KEY `order_detail_order` (`id_order`),
                                   KEY `product_id` (`product_id`),

                                   CONSTRAINT `fk_ps_order_detail_order`
                                       FOREIGN KEY (`id_order`)
                                           REFERENCES `ps_orders` (`id_order`)
                                           ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- DONNÉES DE TEST
-- =========================================================

INSERT INTO `ps_customer`
(`id_customer`, `company`, `firstname`, `lastname`, `email`, `ct_number`, `active`, `date_add`, `date_upd`)
VALUES
    (1, 'Institut Beauté Nancy', 'Claire', 'Martin', 'claire.martin@example.test', 'CT000101', 1, '2026-07-20 09:15:00', '2026-07-20 09:15:00'),
    (2, 'Studio Glow', 'Sophie', 'Bernard', 'sophie.bernard@example.test', 'CT000102', 1, '2026-07-21 10:20:00', '2026-07-21 10:20:00'),
    (3, 'Maison Élégance', 'Laura', 'Petit', 'laura.petit@example.test', 'CT000103', 1, '2026-07-23 08:45:00', '2026-07-23 08:45:00');


INSERT INTO `ps_orders`
(`id_order`, `reference`, `id_customer`, `current_state`,
 `total_paid`, `total_paid_tax_incl`, `total_paid_tax_excl`,
 `total_products`, `total_products_wt`,
 `date_add`, `date_upd`)
VALUES
    (1001, 'ANY000001', 1, 2,
     60.000000, 60.000000, 50.000000,
     50.000000, 60.000000,
     '2026-07-20 10:00:00', '2026-07-20 10:05:00'),

    (1002, 'ANY000002', 2, 2,
     90.000000, 90.000000, 75.000000,
     75.000000, 90.000000,
     '2026-07-22 14:30:00', '2026-07-22 14:42:00'),

    (1003, 'ANY000003', 3, 3,
     36.000000, 36.000000, 30.000000,
     30.000000, 36.000000,
     '2026-07-24 09:20:00', '2026-07-24 09:35:00');


INSERT INTO `ps_order_detail`
(`id_order_detail`, `id_order`, `product_id`, `product_name`,
 `product_quantity`, `product_price`, `product_reference`,
 `total_price_tax_incl`, `total_price_tax_excl`,
 `unit_price_tax_incl`, `unit_price_tax_excl`)
VALUES
    (5001, 1001, 101, 'Rouge à lèvres test 678888-2',
     2, 25.000000, '678888-2',
     60.000000, 50.000000,
     30.000000, 25.000000),

    (5002, 1002, 102, 'Fond de teint test 678888-3',
     1, 37.500000, '678888-3',
     45.000000, 37.500000,
     45.000000, 37.500000),

    (5003, 1002, 103, 'Poudre test 712345-1',
     3, 12.500000, '712345-1',
     45.000000, 37.500000,
     15.000000, 12.500000),

    (5004, 1003, 104, 'Mascara test 745678-2',
     2, 15.000000, '745678-2',
     36.000000, 30.000000,
     18.000000, 15.000000);


-- =========================================================
-- EXEMPLE DE REQUÊTE POUR LE READER
-- ct_number et company viennent de ps_customer
-- =========================================================
-- SELECT
--     o.id_order,
--     o.reference,
--     o.current_state,
--     o.total_paid,
--     o.date_add,
--     o.date_upd,
--     c.company,
--     c.ct_number
-- FROM ps_orders o
-- INNER JOIN ps_customer c ON c.id_customer = o.id_customer
-- WHERE o.date_upd > :lastUpdate
-- ORDER BY o.date_upd ASC;
