<?php

declare(strict_types=1);

return [
    'menu' => [
        'table' => 'menus',
    ],
    'menu_item' => [
        'table' => 'menu_items',
    ],

    'cache' => [
        'key' => 'menu:tree:',
        'ttl' => 360, // min
    ],
];
