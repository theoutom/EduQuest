<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/Auth.php';

$action = $argv[1] ?? '';
$username = Auth::username($argv[2] ?? '');
$role = $argv[3] ?? '';
$name = trim($argv[4] ?? '');
if (!Auth::validUsername($username) || !in_array($action, ['set', 'disable'], true)
    || ($action === 'set' && (!in_array($role, ['student', 'admin'], true) || $name === ''
        || !mb_check_encoding($name, 'UTF-8') || mb_strlen($name) > 80))) {
    fwrite(STDERR, "Usage: php scripts/account.php set <username> <student|admin> <name> (password via stdin)\n");
    fwrite(STDERR, "       php scripts/account.php disable <username>\n");
    exit(1);
}

$hash = null;
if ($action === 'set') {
    $password = rtrim((string) fgets(STDIN, 260), "\r\n");
    if (!mb_check_encoding($password, 'UTF-8') || str_contains($password, "\0")
        || mb_strlen($password) < 15 || strlen($password) > 256) {
        fwrite(STDERR, "Use at least 15 characters and at most 256 UTF-8 bytes.\n");
        exit(1);
    }
    $hash = Auth::hashPassword($password);
    unset($password);
}

try {
    $db = database();
    $db->beginTransaction();
    if ($action === 'set') {
        $insert = $db->prepare(<<<'SQL'
            INSERT INTO eduquest.students (username, name, role, password_hash)
            VALUES (?, ?, ?, ?) ON CONFLICT (username) DO NOTHING
            SQL);
        $insert->execute([$username, $name, $role, $hash]);
    }
    $lookup = $db->prepare('SELECT id FROM eduquest.students WHERE username = ?');
    $lookup->execute([$username]);
    $id = $lookup->fetchColumn();
    if ($id === false) {
        throw new RuntimeException('Account was not found.');
    }
    Auth::lockAccount($db, (int) $id);
    if ($action === 'set') {
        $db->prepare('UPDATE eduquest.students SET name = ?, role = ?, enabled = true, password_hash = ? WHERE id = ?')
            ->execute([$name, $role, $hash, $id]);
    } else {
        $db->prepare('UPDATE eduquest.students SET enabled = false, password_hash = NULL WHERE id = ?')->execute([$id]);
    }
    $db->prepare('DELETE FROM eduquest.sessions WHERE student_id = ?')->execute([$id]);
    $db->prepare('DELETE FROM eduquest.login_limits WHERE bucket = ?')
        ->execute(['account:' . hash('sha256', $username)]);
    $db->prepare('INSERT INTO eduquest.security_events (account_id, event) VALUES (?, ?)')
        ->execute([$id, $action === 'set' ? 'account_configured' : 'account_disabled']);
    $db->commit();
    echo "Account updated. Previous sessions were revoked.\n";
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, $error instanceof PDOException ? "Database operation failed. Check the maintenance connection.\n" : $error->getMessage() . PHP_EOL);
    exit(1);
}
