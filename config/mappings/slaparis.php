<?php

return [
    'slaparis' => [
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

            'customer_date_upd' => 'updatedAt',
        ],
    ],
];
