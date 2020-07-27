<?php
/**
 * User: Lingtima<lingtima@gmail.com>
 * Date: 2020/7/23 14:32
 */

$env = parse_ini_file(__DIR__ . '/../.env');

return [
    'db' => [
        'database_type' => $env['DB_TYPE'] ?? 'mysql',
        'server' => $env['DB_HOST'] ?? '127.0.0.1',
        'database_name' => $env['DB_NAME'] ?? '',
        'username' => $env['DB_USERNAME'] ?? '',
        'password' => $env['DB_PASSWORD'] ?? '',

        'charset' => $env['DB_CHARSET'] ?? 'utf8mb4',
        'collation' => $env['DB_COLLATION'] ?? 'utf8mb4_union_ci',
        'port' => $env['DB_PORT'] ?? 3306,

        'logging' => true,
    ],
    'redis' => [
        'scheme' => $env['scheme'] ?? 'tcp',
        'host' => $env['host'] ?? '127.0.0.1',
        'port' => $env['port'] ?? '6379',
    ],
];