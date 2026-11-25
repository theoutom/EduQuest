<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/QuizService.php';

$mode = $argv[1] ?? '';
if (!in_array($mode, ['prepare', 'verify'], true)) {
    throw new InvalidArgumentException('Expected prepare or verify.');
}

$db = database();
if (!preg_match('/^eduquest_test_[a-f0-9]{16}$/D', $db->query('SELECT current_database()')->fetchColumn())) {
    throw new RuntimeException('Upgrade rehearsal requires a temporary test database.');
}
$quiz = new QuizService($db);

if ($mode === 'prepare') {
    $db->beginTransaction();
    $db->exec('CREATE SCHEMA eduquest_meta');
    $db->exec('REVOKE ALL ON SCHEMA eduquest_meta FROM PUBLIC');
    $db->exec('CREATE TABLE eduquest_meta.migrations (name text PRIMARY KEY, applied_at timestamptz DEFAULT now())');
    $record = $db->prepare('INSERT INTO eduquest_meta.migrations (name) VALUES (?)');
    $legacy = glob(dirname(__DIR__) . '/database/00[1-9]_*.sql');
    if (count($legacy) !== 9) {
        throw new RuntimeException('Expected nine baseline migrations.');
    }
    foreach ($legacy as $file) {
        $db->exec(file_get_contents($file));
        $record->execute([basename($file)]);
    }
    $db->commit();

    $attemptId = (int) $quiz->start(1, 'struktur-data')['id'];
    $question = $quiz->attempt(1, $attemptId)['questions'][0];
    $quiz->save(1, $attemptId, (int) $question['id'], (int) $question['options'][0]['id'], false);
    $quiz->submit(1, $attemptId);
    exit;
}

$expected = array_map('basename', glob(dirname(__DIR__) . '/database/[0-9]*.sql'));
$applied = $db->query('SELECT name FROM eduquest_meta.migrations ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
sort($expected, SORT_STRING);
if ($applied !== $expected) {
    throw new RuntimeException('Applied migrations do not match the source migrations.');
}

$review = $quiz->review(1, 1);
if ($review['score'] !== 33 || $review['correct'] !== 1 || $review['blank'] !== 2
    || $review['questions'][0]['outcome'] !== 'benar' || $review['questions'][0]['explanation'] === '') {
    throw new RuntimeException('A completed legacy attempt changed after migration.');
}
try {
    $quiz->review(2, 1);
    throw new RuntimeException('A different student can read the legacy attempt.');
} catch (QuizProblem $problem) {
    if ($problem->status !== 404) {
        throw $problem;
    }
}
echo "PASS: legacy migration ledger, submitted answer and private review survive upgrading through the current schema.\n";
