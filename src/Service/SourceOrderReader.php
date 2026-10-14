<?php

namespace App\Service;

use PDO;

class SourceOrderReader
{
    public function __construct(
        private readonly SourceConnection $sourceConnection,
    )
    {
    }

    /**
     * Récupère les commandes modifiées depuis une date donnée.
     *
     * @return array<int, array<string, mixed>>
     */
    public function readOrders(
        string              $source,
        ?\DateTimeInterface $lastUpdate = null
    ): array
    {

        $pdo = $this->sourceConnection->connect($source);
        $config = $this->sourceConnection->getConfig($source);

        $prefix = $config['prefix'];

        $sql = $this->getOrderQuery( $source, $prefix);

        $statement = $pdo->prepare($sql);

        $statement->execute([
            'lastUpdate' => $lastUpdate?->format('Y-m-d H:i:s')
                    ?? '1970-01-01 00:00:00',
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les lignes correspondant à une commande source.
     *
     * @return array<int, array<string, mixed>>
     */
    public function readOrderItems(
        string     $source,
        int|string $sourceOrderId
    ): array
    {
        $pdo = $this->sourceConnection->connect($source);
        $config = $this->sourceConnection->getConfig($source);

        $prefix = $config['prefix'];

        $sql = sprintf(
            '
                SELECT
                    *
                FROM %sorder_detail
                WHERE id_order = :orderId
                ORDER BY id_order_detail ASC
            ',
            $prefix
        );

        $statement = $pdo->prepare($sql);

        $statement->execute([
            'orderId' => $sourceOrderId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les commandes avec leurs lignes brutes.
     *
     * @return array<int, array{
     *     order: array<string, mixed>,
     *     items: array<int, array<string, mixed>>
     * }>
     */
    public function readOrdersWithItems(
        string              $source,
        ?\DateTimeInterface $lastUpdate = null
    ): array
    {
        $orders = $this->readOrders($source, $lastUpdate);

        $result = [];

        foreach ($orders as $order) {
            if (!isset($order['id_order'])) {
                throw new \RuntimeException(
                    sprintf(
                        'La commande provenant de la source "%s" ne contient pas de champ "id_order".',
                        $source
                    )
                );
            }

            $result[] = [
                'order' => $order,
                'items' => $this->readOrderItems(
                    $source,
                    $order['id_order']
                ),
            ];
        }

        return $result;
    }

    private function getOrderQuery(string $source, string $prefix): string
    {

        return match ($source) {
            'anyskin' => sprintf(
                '
            SELECT
                o.*,
                c.company,
                c.ct_number,
                c.email
            FROM %sorders o
            LEFT JOIN %scustomer c
                ON c.id_customer = o.id_customer
            WHERE o.date_upd > :lastUpdate
            ORDER BY o.date_upd ASC
            ',
                $prefix,
                $prefix
            ),

            'slaparis' => sprintf(
                '
            SELECT
                o.*,
                c.company,
                c.email
            FROM %sorders o
            LEFT JOIN %scustomer c
                ON c.id_customer = o.id_customer
            WHERE o.date_upd > :lastUpdate
            ORDER BY o.date_upd ASC
            ',
                $prefix,
                $prefix
            ),

            default => sprintf(
                '
            SELECT
                o.*,
                c.company,
                -- c.ct_number,
                c.email
            FROM %sorders o
            LEFT JOIN %scustomer c
                ON c.id_customer = o.id_customer
            WHERE o.date_upd > :lastUpdate
            ORDER BY o.date_upd ASC
            ',
                $prefix,
                $prefix
            ),

        };
    }
}
