<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require "$root/app/database.php";
require "$root/app/Auth.php";
require "$root/app/QuizService.php";

$db = database();
$auth = new Auth($db);
$password = getenv('EDUQUEST_TEST_PASSWORD');
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
};

$stored = $db->query("SELECT password_hash FROM eduquest.students WHERE username = 'operator'")->fetchColumn();
$assert(is_string($stored) && str_starts_with($stored, '$argon2id$') && password_verify($password, $stored), 'Admin password is stored as Argon2id');
$idle = $auth->login('operator', $password, 'fixture');
$assert($auth->current($idle['token'])['role'] === 'admin', 'Fresh admin session is active');
$db->prepare("UPDATE eduquest.sessions SET last_seen_at = clock_timestamp() - interval '11 minutes' WHERE token_hash = ?")
    ->execute([hash('sha256', $idle['token'])]);
$assert($auth->current($idle['token']) === null, 'Admin session expires after inactivity');

$absolute = $auth->login('operator', $password, 'fixture');
$db->prepare("UPDATE eduquest.sessions SET expires_at = clock_timestamp() - interval '1 second' WHERE token_hash = ?")
    ->execute([hash('sha256', $absolute['token'])]);
$assert($auth->current($absolute['token']) === null, 'Absolute expiry wins over recent activity');

$active = $auth->login('operator', $password, 'fixture');
$db->exec("UPDATE eduquest.students SET role = 'student' WHERE username = 'operator'");
$assert($auth->current($active['token']) === null, 'An existing admin cookie cannot bypass a changed role');
$db->exec("UPDATE eduquest.students SET role = 'admin' WHERE username = 'operator'");
$auth->logout($active['token']);
