<?php
declare(strict_types=1);

return [
    'database' => [
        'host' => getenv('AGRILOOP_DB_HOST') ?: '127.0.0.1',
        'name' => getenv('AGRILOOP_DB_NAME') ?: 'agriloop',
        'user' => getenv('AGRILOOP_DB_USER') ?: '',
        'password' => getenv('AGRILOOP_DB_PASSWORD') ?: '',
    ],
];
