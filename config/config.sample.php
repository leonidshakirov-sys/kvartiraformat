<?php

declare(strict_types=1);

return [
    'app' => [
        'env' => 'production',
        'debug' => false,
        'base_url' => 'https://kvartiraformat.ru',
        'force_https' => true,
        'canonical_host' => 'kvartiraformat.ru',
        'session_name' => 'kf_admin_session',
        'csrf_key' => 'change-this-random-string-in-config-php',
    ],
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'your_database_name',
        'username' => 'your_database_user',
        'password' => 'your_database_password',
        'charset' => 'utf8mb4',
    ],
    'telegram' => [
        'bot_token' => '',
        'chat_id' => '',
        'connect_timeout' => 5,
        'timeout' => 10,
    ],
    'mail' => [
        'to' => '',
    ],
    'security' => [
        'admin_login_limit' => 7,
        'admin_login_window_minutes' => 20,
        'lead_duplicate_window_minutes' => 15,
        'lead_retention_days' => 365,
        'upload_max_bytes' => 5242880,
        'document_max_bytes' => 8388608,
        'allowed_image_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'avif'],
        'allowed_document_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'],
    ],
];
