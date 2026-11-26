<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/QuizService.php';
require dirname(__DIR__) . '/app/ClassService.php';
require dirname(__DIR__) . '/app/MasteryService.php';
require dirname(__DIR__) . '/app/MinigameService.php';
require dirname(__DIR__) . '/app/QuestService.php';

$db = database();
$fixture = file_get_contents(dirname(__DIR__) . '/scripts/demo-pilot.sql');
$secret = 'fixture-only-teacher-password';
$hash = password_hash($secret, PASSWORD_ARGON2ID);
$fixture = str_replace('__TEACHER_PASSWORD_HASH__', $hash, $fixture);
foreach ([['pilot-admin', 'admin'], ['pilot-siswa', 'student'], ['pilot-teman', 'student']] as [$username, $role]) {
    $statement = $db->prepare('INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES (?, ?, ?, true, ?)');
    $statement->execute([$username, $username, $role, $hash]);
}
$db->exec("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('demo-guru', 'Existing user', 'student', true, 'fixture')");
try {
    $db->exec($fixture);
    throw new RuntimeException('Conflicting username was overwritten.');
} catch (PDOException $error) {
    if ($error->getCode() !== 'P0001') { throw $error; }
}
if ((int) $db->query("SELECT count(*) FROM eduquest.classes WHERE name LIKE '%(Demo)'")->fetchColumn() !== 0) {
    throw new RuntimeException('A conflict must leave no partial fixture.');
}
$db->exec("DELETE FROM eduquest.students WHERE username = 'demo-guru'");
$db->exec("INSERT INTO eduquest.classes (name, academic_year) VALUES ('[DEMO] XI PPLG A', '2026/2027'), ('[DEMO] XI PPLG B', '2026/2027')");
$db->exec("INSERT INTO eduquest.class_students (class_id, student_id) SELECT c.id, s.id FROM eduquest.classes c JOIN eduquest.students s ON (c.name = '[DEMO] XI PPLG A' AND s.username = 'pilot-siswa') OR (c.name = '[DEMO] XI PPLG B' AND s.username = 'pilot-teman')");
$db->exec($fixture);
$db->exec($fixture);
$partnerFixture = str_replace('__PARTNER_PASSWORD_HASH__', $hash, file_get_contents(dirname(__DIR__) . '/scripts/demo-partner.sql'));
$db->exec($partnerFixture);
$db->exec($partnerFixture);
$partner = $db->query("SELECT id, password_hash FROM eduquest.students WHERE username = 'demo-partner' AND enabled")->fetch();
if (!$partner || !password_verify($secret, $partner['password_hash'])) { throw new RuntimeException('Demo PvP partner is unavailable.'); }

$teacher = $db->query("SELECT id, password_hash FROM eduquest.students WHERE username = 'demo-guru' AND enabled AND role = 'teacher'")->fetch();
if (!$teacher || !password_verify($secret, $teacher['password_hash'])) { throw new RuntimeException('Demo teacher account is unavailable.'); }
$student = (int) $db->query("SELECT id FROM eduquest.students WHERE username = 'pilot-teman'")->fetchColumn();
$peer = (int) $db->query("SELECT id FROM eduquest.students WHERE username = 'pilot-siswa'")->fetchColumn();
$quiz = new QuizService($db);
$overview = $quiz->overview($student, 'demo-pplg-b-dasar');
if (!$quiz->overview((int) $partner['id'], 'demo-pplg-b-dasar')) { throw new RuntimeException('Demo partner cannot access the B quiz.'); }
if ((int) $overview['attempt_limit'] !== 5 || count($overview['history']) !== 1 || (int) $overview['history'][0]['score'] !== 75) {
    throw new RuntimeException('Synthetic 75-point result or remaining attempts are wrong.');
}
try { $quiz->overview($peer, 'demo-pplg-b-dasar'); throw new RuntimeException('Other class can access the B quiz.'); }
catch (QuizProblem $problem) { if ($problem->status !== 404) { throw $problem; } }
$review = $quiz->review($student, (int) $overview['history'][0]['id']);
if ($review['score'] !== 75 || $review['correct'] !== 3 || $review['wrong'] !== 1) { throw new RuntimeException('Sample result is inconsistent.'); }
$classes = new ClassService($db);
if (count($classes->teacherClasses((int) $teacher['id'])) !== 1 || count($classes->teacherResults((int) $teacher['id'], (int) $overview['id'])) !== 1) {
    throw new RuntimeException('Teacher cannot inspect the sample class and result.');
}
$mastery = (new MasteryService($db))->visible($student);
if (array_column($mastery, 'status') !== ['mastered', 'available', 'locked'] || (new MasteryService($db))->visible($peer) !== []) {
    throw new RuntimeException('Mastery sample states or class privacy are wrong.');
}
if (count((new QuestService($db))->visible($student)) !== 2 || (new QuestService($db))->visible($peer) !== []) {
    throw new RuntimeException('Quest sample visibility is wrong.');
}
if (count((new MinigameService($db))->catalog($student)) !== 1 || (new MinigameService($db))->catalog($peer) !== []) {
    throw new RuntimeException('Minigame sample visibility is wrong.');
}
foreach (['classes' => 2, 'quizzes' => 2, 'quests' => 2, 'mastery_nodes' => 3, 'minigames' => 1] as $table => $expected) {
    $conditions = match ($table) {
        'classes' => "name LIKE '%(Demo)' OR name LIKE '[DEMO]%'",
        'quizzes' => "slug LIKE 'demo-pplg-b-%'",
        'quests' => "title LIKE 'Quest Demo:%'",
        'mastery_nodes' => "created_by = " . (int) $teacher['id'],
        default => "title LIKE 'Demo Susun Langkah:%'",
    };
    if ((int) $db->query("SELECT count(*) FROM eduquest.$table WHERE $conditions")->fetchColumn() !== $expected) {
        throw new RuntimeException("Fixture is not idempotent for $table.");
    }
}
echo "PASS: safe, idempotent class-scoped demonstration data and synthetic history.\n";
