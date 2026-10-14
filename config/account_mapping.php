<?php

return [
    'anyskin' => [
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
];
