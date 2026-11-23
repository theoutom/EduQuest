<?php

declare(strict_types=1);

$operation = $argv[1] ?? '';
$name = $argv[2] ?? '';
if (!in_array($operation, ['create', 'drop'], true) || !preg_match('/^eduquest_test_[a-f0-9]{16}$/D', $name)) {
    fwrite(STDERR, "Expected a temporary EduQuest test database name.\n");
    exit(1);
}

$dsn = getenv('EDUQUEST_TEST_DSN') ?: 'pgsql:host=127.0.0.1;port=5432;dbname=postgres';
$testDsn = preg_replace('/(^|;)dbname=[^;]*/', '$1dbname=' . $name, $dsn, 1, $replacements);
if ($replacements !== 1) {
    fwrite(STDERR, "EDUQUEST_TEST_DSN must include dbname.\n");
    exit(1);
}

$user = getenv('EDUQUEST_DB_USER') ?: 'eduquest';
$password = getenv('EDUQUEST_DB_PASSWORD') ?: '';
$db = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ($operation === 'create') {
    $db->exec('CREATE DATABASE ' . $name . ' TEMPLATE template0 ENCODING \'UTF8\'');
    echo $testDsn;
} else {
    $db->exec('DROP DATABASE ' . $name);
}
