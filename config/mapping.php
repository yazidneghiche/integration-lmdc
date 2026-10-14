<?php


$mapping = [
    /*
     * Mapping PrestaShop standard.
     *
     * Il est utilisé lorsqu'aucun mapping spécifique
     * n'existe pour une source.
     */
    'prestashop' => [
        'order' => [
            'id_order' => 'sourceOrderId',
            'company' => 'company',
            'date_add' => 'createdAt',
            'total_paid' => 'total',
            'current_state' => 'status',
            'date_upd' => 'lastUpdate',
            'total_paid_tax_excl' => 'totalHt',
            'total_paid_tax_incl' => 'totalTtc',
        ],

        'orderProduct' => [
            'id_order_detail' => 'sourceItemId',
            'product_reference' => 'reference',
            'product_name' => 'name',
            'total_price_tax_excl' => 'price',
            'product_quantity' => 'quantity',
            'unit_price_tax_excl' => 'unitPrice',
        ],

        'account' => [
            // Table customer
            'id_customer' => 'sourceCustomerId',
            'company' => 'company',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'email',
            'siret' => 'siret',

            // Table address
            'phone' => 'phone',
            'address1' => 'address',
            'address2' => 'addressComplement',
            'vat_number' => 'vat',
            'postcode' => 'zipcode',
            'city' => 'city',
            'country' => 'country',

            // Synchronisation
            'customer_date_upd' => 'updatedAt',
        ],
    ],
];

/*
 * Les erreurs des mappings spécifiques sont enregistrées
 * dans var/log/mapping/mapping-AAAA-MM-JJ.log.
 */
$logDirectory = dirname(__DIR__) . '/var/log/mapping';

if (!is_dir($logDirectory)) {
    @mkdir($logDirectory, 0775, true);
}

$logFile = sprintf(
    '%s/mapping-%s.log',
    $logDirectory,
    date('Y-m-d')
);

/*
 * Enregistre une erreur une seule fois par jour.
 */
$logMappingError = static function (
    string $mappingFile,
    string $message
) use ($logFile): void {
    $identifier = hash(
        'sha256',
        basename($mappingFile) . '|' . $message
    );

    $marker = sprintf('[%s]', $identifier);

    $existingLogs = is_file($logFile)
        ? (string)file_get_contents($logFile)
        : '';

    if (str_contains($existingLogs, $marker)) {
        return;
    }

    $line = sprintf(
        '[%s] %s Fichier : %s | Erreur : %s%s',
        date('Y-m-d H:i:s'),
        $marker,
        basename($mappingFile),
        $message,
        PHP_EOL
    );

    @file_put_contents(
        $logFile,
        $line,
        FILE_APPEND | LOCK_EX
    );
};

/*
 * Charge les mappings propres à chaque source.
 *
 * Exemple :
 * config/mappings/anyskin.php
 *
 * Les fichiers .dist ne sont pas chargés.
 */
$mappingFiles = glob(__DIR__ . '/mappings/*.php') ?: [];

foreach ($mappingFiles as $mappingFile) {
    try {
        $sourceMappings = require $mappingFile;
    } catch (\Throwable $exception) {
        $logMappingError(
            $mappingFile,
            $exception->getMessage()
        );

        continue;
    }

    if (!is_array($sourceMappings)) {
        $logMappingError(
            $mappingFile,
            'Le fichier ne retourne pas un tableau.'
        );

        continue;
    }

    foreach ($sourceMappings as $source => $sourceMapping) {
        if (
            !is_string($source)
            || !preg_match('/^[a-z0-9_-]+$/', $source)
            || !is_array($sourceMapping)
        ) {
            $logMappingError(
                $mappingFile,
                'Le code source ou son mapping est invalide.'
            );

            continue;
        }

        if (
            !isset(
                $sourceMapping['order'],
                $sourceMapping['orderProduct'],
                $sourceMapping['account']
            )
        ) {
            $logMappingError(
                $mappingFile,
                sprintf(
                    'La source "%s" doit contenir les sections '
                    . 'order, orderProduct et account.',
                    $source
                )
            );

            continue;
        }

        if (
            !is_array($sourceMapping['order'])
            || !is_array($sourceMapping['orderProduct'])
            || !is_array($sourceMapping['account'])
        ) {
            $logMappingError(
                $mappingFile,
                sprintf(
                    'Les sections du mapping "%s" doivent être des tableaux.',
                    $source
                )
            );

            continue;
        }

        /*
         * Le mapping spécifique remplace entièrement
         * le mapping éventuel portant le même nom.
         */
        $mapping[$source] = $sourceMapping;
    }
}

return $mapping;


/*return [

    //Copy this bloc
    'anyskin' => [

        'order' => [
            'id_order' => 'sourceOrderId',
            'company' => 'company',
            'ct_number' => 'ctNumber',
            'date_add' => 'createdAt',
            'total_paid' => 'total',
            'current_state' => 'status',
            'date_upd' => 'lastUpdate',
            'total_paid_tax_excl' => 'totalHt',
            'total_paid_tax_incl' => 'totalTtc',
        ],

        'orderProduct' => [
            'id_order_detail' => 'sourceItemId',
            'product_reference' => 'reference',
            'product_name' => 'name',
            'total_price_tax_excl' => 'price',
            'product_quantity' => 'quantity',
            'unit_price_tax_excl' => 'unitPrice',
        ],

        'account' => [
            // Table customer
            'id_customer' => 'sourceCustomerId',
            'company' => 'company',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'email',
            'siret' => 'siret',

            // Table address
            'phone' => 'phone',
            'address1' => 'address',
            'address2' => 'addressComplement',
            'vat_number' => 'vat',
            'postcode' => 'zipcode',
            'city' => 'city',
            'country' => 'country',

            // Champ personnalisé éventuel
            'ct_number' => 'ctNumber',

            // Synchronisation
            'customer_date_upd' => 'updatedAt',
        ],
    ],
    'prestashop' => [

        'order' => [
            'id_order' => 'sourceOrderId',
            'company' => 'company',
            'ct_number' => 'ctNumber',
            'date_add' => 'createdAt',
            'total_paid' => 'total',
            'current_state' => 'status',
            'date_upd' => 'lastUpdate',
            'total_paid_tax_excl' => 'totalHt',
            'total_paid_tax_incl' => 'totalTtc',
        ],

        'orderProduct' => [
            'id_order_detail' => 'sourceItemId',
            'product_reference' => 'reference',
            'product_name' => 'name',
            'total_price_tax_excl' => 'price',
            'product_quantity' => 'quantity',
            'unit_price_tax_excl' => 'unitPrice',
        ],
        'account' => [
            // Table customer
            'id_customer' => 'sourceCustomerId',
            'company' => 'company',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'email',
            'siret' => 'siret',

            // Table address
            'phone' => 'phone',
            'address1' => 'address',
            'address2' => 'addressComplement',
            'vat_number' => 'vat',
            'postcode' => 'zipcode',
            'city' => 'city',
            'country' => 'country',

            // Champ personnalisé éventuel
            'ct_number' => 'ctNumber',

            // Synchronisation
            'customer_date_upd' => 'updatedAt',
        ],
    ],
    'slaparis' => [
        'order' => [
            'id_order' => 'sourceOrderId',
            'company' => 'company',
            'ct_number' => 'ctNumber',
            'date_add' => 'createdAt',
            'total_paid' => 'total',
            'current_state' => 'status',
            'date_upd' => 'lastUpdate',
            'total_paid_tax_excl' => 'totalHt',
            'total_paid_tax_incl' => 'totalTtc',
        ],
        'orderProduct' => [
            'id_order_detail' => 'sourceItemId',
            'product_reference' => 'reference',
            'product_name' => 'name',
            'total_price_tax_excl' => 'price',
            'product_quantity' => 'quantity',
            'unit_price_tax_excl' => 'unitPrice',
        ],
        'account' => [
            'id_customer' => 'sourceCustomerId',
            'company' => 'company',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'email',
            'siret' => 'siret',
            'phone' => 'phone',
            'address1' => 'address',
            'address2' => 'addressComplement',
            'vat_number' => 'vat',
            'postcode' => 'zipcode',
            'city' => 'city',
            'country' => 'country',
            'ct_number' => 'ctNumber',
            'customer_date_upd' => 'updatedAt',
        ],
    ],
];*/
