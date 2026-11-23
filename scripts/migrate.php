<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';

$db = database();
$db->beginTransaction();
try {
    $db->query('SELECT pg_advisory_xact_lock(476152001)');
    $db->exec('CREATE SCHEMA IF NOT EXISTS eduquest_meta');
    $db->exec('REVOKE ALL ON SCHEMA eduquest_meta FROM PUBLIC');
    $db->exec('CREATE TABLE IF NOT EXISTS eduquest_meta.migrations (name text PRIMARY KEY, applied_at timestamptz DEFAULT now())');
    $applied = $db->query('SELECT name FROM eduquest_meta.migrations')->fetchAll(PDO::FETCH_COLUMN);
    $mark = $db->prepare('INSERT INTO eduquest_meta.migrations (name) VALUES (?)');
    $ran = [];
    foreach (glob(dirname(__DIR__) . '/database/[0-9]*.sql') as $file) {
        $name = basename($file);
        if (!in_array($name, $applied, true)) {
            $db->exec(file_get_contents($file));
            $mark->execute([$name]);
            $ran[] = $name;
        }
    }
    $db->commit();
    echo $ran === [] ? "Database is up to date.\n" : 'Applied ' . implode(', ', $ran) . PHP_EOL;
} catch (Throwable $error) {
    $db->rollBack();
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
