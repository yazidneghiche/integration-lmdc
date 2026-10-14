<?php

return [
    // Activez uniquement sur une installation et une base PrestaShop de test.
    'enabled' => true,

    // Ajoutez ici les codes des sources de test définies dans config/sources.php.
    'allowed_sources' => [
        'prestashop',
    ],

    // Le premier client absent de PrestaShop et de l'intégration sera utilisé.
    'customers' => [
        ['firstname' => 'Alice', 'lastname' => 'Martin', 'email' => 'test.sync.alice@example.test'],
        ['firstname' => 'Benoit', 'lastname' => 'Robert', 'email' => 'test.sync.benoit@example.test'],
        ['firstname' => 'Claire', 'lastname' => 'Bernard', 'email' => 'test.sync.claire@example.test'],
        ['firstname' => 'David', 'lastname' => 'Petit', 'email' => 'test.sync.david@example.test'],
        ['firstname' => 'Emma', 'lastname' => 'Durand', 'email' => 'test.sync.emma@example.test'],
        ['firstname' => 'Farid', 'lastname' => 'Leroy', 'email' => 'test.sync.farid@example.test'],
        ['firstname' => 'Gaelle', 'lastname' => 'Moreau', 'email' => 'test.sync.gaelle@example.test'],
        ['firstname' => 'Hugo', 'lastname' => 'Simon', 'email' => 'test.sync.hugo@example.test'],
        ['firstname' => 'Ines', 'lastname' => 'Laurent', 'email' => 'test.sync.ines@example.test'],
        ['firstname' => 'Jules', 'lastname' => 'Michel', 'email' => 'test.sync.jules@example.test'],
        ['firstname' => 'Karine', 'lastname' => 'Garcia', 'email' => 'test.sync.karine@example.test'],
        ['firstname' => 'Lucas', 'lastname' => 'David', 'email' => 'test.sync.lucas@example.test'],
        ['firstname' => 'Manon', 'lastname' => 'Roux', 'email' => 'test.sync.manon@example.test'],
        ['firstname' => 'Nabil', 'lastname' => 'Vincent', 'email' => 'test.sync.nabil@example.test'],
        ['firstname' => 'Olivia', 'lastname' => 'Fournier', 'email' => 'test.sync.olivia@example.test'],
        ['firstname' => 'Paul', 'lastname' => 'Morel', 'email' => 'test.sync.paul@example.test'],
        ['firstname' => 'Rania', 'lastname' => 'Girard', 'email' => 'test.sync.rania@example.test'],
        ['firstname' => 'Sami', 'lastname' => 'Andre', 'email' => 'test.sync.sami@example.test'],
        ['firstname' => 'Tania', 'lastname' => 'Mercier', 'email' => 'test.sync.tania@example.test'],
        ['firstname' => 'Yanis', 'lastname' => 'Dupont', 'email' => 'test.sync.yanis@example.test'],
    ],
];
