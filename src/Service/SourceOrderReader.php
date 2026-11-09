<?php

namespace App\Service;

use PDO;

class SourceOrderReader
{
    /**
     * Présence de la colonne ct_number dans la table customer, par source.
     *
     * @var array<string, bool>
     */
    private array $ctNumberColumn = [];

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

        return $this->fetchOrders($pdo, $source, $lastUpdate);
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

        return $this->fetchOrderItems($pdo, $source, $sourceOrderId);
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
        // Une seule connexion pour les commandes et toutes leurs lignes
        $pdo = $this->sourceConnection->connect($source);

        $orders = $this->fetchOrders($pdo, $source, $lastUpdate);

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
                'items' => $this->fetchOrderItems(
                    $pdo,
                    $source,
                    $order['id_order']
                ),
            ];
        }

        return $result;
    }

    /**
     * Construit la requête des commandes.
     *
     * c.ct_number n'existe pas dans une table customer PrestaShop standard :
     * la colonne n'est sélectionnée que si la boutique l'a ajoutée.
     */
    public function buildOrderQuery(string $prefix, bool $withCtNumber): string
    {
        return sprintf(
            '
            SELECT
                o.*,
                c.company,%s
                c.email
            FROM %sorders o
            LEFT JOIN %scustomer c
                ON c.id_customer = o.id_customer
            WHERE o.date_upd > :lastUpdate
            ORDER BY o.date_upd ASC
            ',
            $withCtNumber ? "\n                c.ct_number," : '',
            $prefix,
            $prefix
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchOrders(
        PDO                 $pdo,
        string              $source,
        ?\DateTimeInterface $lastUpdate
    ): array
    {
        $prefix = $this->sourceConnection->getConfig($source)['prefix'];

        $sql = $this->buildOrderQuery(
            $prefix,
            $this->hasCtNumberColumn($pdo, $source, $prefix)
        );

        $statement = $pdo->prepare($sql);

        $statement->execute([
            'lastUpdate' => $lastUpdate?->format('Y-m-d H:i:s')
                    ?? '1970-01-01 00:00:00',
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchOrderItems(
        PDO        $pdo,
        string     $source,
        int|string $sourceOrderId
    ): array
    {
        $prefix = $this->sourceConnection->getConfig($source)['prefix'];

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
     * Vérifie une seule fois par source si la table customer contient ct_number.
     */
    private function hasCtNumberColumn(PDO $pdo, string $source, string $prefix): bool
    {
        if (!isset($this->ctNumberColumn[$source])) {
            $statement = $pdo->query(
                sprintf("SHOW COLUMNS FROM `%scustomer` LIKE 'ct_number'", $prefix)
            );

            $this->ctNumberColumn[$source] = $statement !== false
                && $statement->fetch(PDO::FETCH_ASSOC) !== false;
        }

        return $this->ctNumberColumn[$source];
    }
}
