<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/QuizService.php';
require dirname(__DIR__) . '/app/MinigameService.php';

function expectMiniProblem(callable $op, int $status): void {
    try { $op(); } catch (QuizProblem $problem) {
        if ($problem->status === $status) { return; }
        throw $problem;
    }
    throw new RuntimeException("Expected HTTP $status from minigame.");
}
$db = database();
$db->exec("UPDATE eduquest.students SET enabled = true, password_hash = 'fixture' WHERE id IN (1, 2)");
$class = (int) $db->query("INSERT INTO eduquest.classes (name, academic_year) VALUES ('Game B', '2026/2027') RETURNING id")->fetchColumn();
$teacher = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('guru-game', 'Guru Game', 'teacher', true, 'fixture') RETURNING id")->fetchColumn();
$db->exec("INSERT INTO eduquest.class_teachers VALUES ($class, $teacher)");
$db->exec("INSERT INTO eduquest.class_students VALUES ($class, 1)");
$games = new MinigameService($db);
$data = ['title' => 'Urutkan', 'instruction' => 'Urutkan langkah', 'explanation' => 'Mulai dari awal.', 'steps' => "Langkah satu\nLangkah dua\nLangkah tiga", 'class_id' => (string) $class, 'xp' => '20'];
$gameId = $games->create($teacher, $data);
expectMiniProblem(fn () => $games->create($teacher, array_replace($data, ['class_id' => '0'])), 403);
expectMiniProblem(fn () => $games->show(2, $gameId), 404);
$game = $games->show(1, $gameId);
if (count($game['steps']) !== 3 || array_key_exists('position', $game['steps'][0]) || array_key_exists('explanation', $game)) {
    throw new RuntimeException('The play view must not reveal the correct sequence or explanation.');
}
$order = $db->query("SELECT token FROM eduquest.minigame_steps WHERE game_id = $gameId ORDER BY position")->fetchAll(PDO::FETCH_COLUMN);
expectMiniProblem(fn () => $games->submit(1, $gameId, str_repeat('a', 32), [$order[0], $order[0], $order[2]]), 422);
$wrong = $games->submit(1, $gameId, str_repeat('a', 32), array_reverse($order));
if ($wrong['correct'] || (int) $wrong['earned_xp'] !== 0) { throw new RuntimeException('Incorrect ordering must not earn XP.'); }
$same = $games->submit(1, $gameId, str_repeat('a', 32), $order);
if ((int) $same['id'] !== (int) $wrong['id'] || $same['correct']) { throw new RuntimeException('A retry must return the original result.'); }
$correct = $games->submit(1, $gameId, str_repeat('b', 32), $order);
if (!$correct['correct'] || (int) $correct['earned_xp'] !== 20 || $correct['explanation'] !== 'Mulai dari awal.') { throw new RuntimeException('Correct ordering should earn XP and show the explanation.'); }
$next = $games->submit(1, $gameId, str_repeat('c', 32), $order);
if ((int) $next['earned_xp'] !== 0 || $games->totalXp(1) !== 20) { throw new RuntimeException('Repeated wins cannot duplicate XP.'); }
expectMiniProblem(fn () => $games->result(2, (int) $correct['id']), 404);
$db->exec("DELETE FROM eduquest.class_students WHERE class_id = $class AND student_id = 1");
expectMiniProblem(fn () => $games->show(1, $gameId), 404);
if (!$games->result(1, (int) $correct['id'])['correct']) { throw new RuntimeException('Completed history must remain available to its owner.'); }
echo "PASS: class access, secret sequence, server scoring and idempotent minigame rewards.\n";
