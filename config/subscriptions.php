<?php

return [
    // Prefix used in permission/feature naming conventions
    'permission_prefix' => 'manage_',

    // Resources that should inherit access from other resources
    // e.g. categories often ride on products access
    'resource_aliases' => [
        'categories' => ['products'],
        'payments' => ['orders'],
    ],
];
