<?php

declare(strict_types=1);

function database(): PDO
{
    $dsn = getenv('EDUQUEST_DB_DSN') ?: 'pgsql:host=127.0.0.1;port=5432;dbname=eduquest';
    return connectPostgres($dsn, getenv('EDUQUEST_DB_USER') ?: 'eduquest', getenv('EDUQUEST_DB_PASSWORD') ?: '');
}

function adminDatabase(): PDO
{
    $user = getenv('EDUQUEST_ADMIN_DB_USER');
    if (!$user) {
        throw new QuizProblem(503, 'Koneksi editor admin belum dikonfigurasi.');
    }
    $dsn = getenv('EDUQUEST_ADMIN_DB_DSN') ?: (getenv('EDUQUEST_DB_DSN') ?: 'pgsql:host=127.0.0.1;port=5432;dbname=eduquest');
    return connectPostgres($dsn, $user, getenv('EDUQUEST_ADMIN_DB_PASSWORD') ?: '');
}

function connectPostgres(string $dsn, string $user, string $password): PDO
{
    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => getenv('EDUQUEST_DB_EMULATE_PREPARES') === '1',
    ]);
}
