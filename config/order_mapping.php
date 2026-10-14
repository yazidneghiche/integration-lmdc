<?php

return [

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
    ],
];
