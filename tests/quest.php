<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/QuizService.php';
require dirname(__DIR__) . '/app/QuestService.php';

$db = database();
$quizId = (int) $db->query("SELECT id FROM eduquest.quizzes WHERE slug = 'struktur-data'")->fetchColumn();
$insert = $db->prepare("INSERT INTO eduquest.quests (title, quiz_id, starts_at, ends_at, target, xp) VALUES ('Uji quest', ?, clock_timestamp() - interval '1 hour', clock_timestamp() + interval '1 hour', 1, 25) RETURNING id");
$insert->execute([$quizId]);
$questId = (int) $insert->fetchColumn();
$quiz = new QuizService($db);
$attempt = (int) $quiz->start(1, 'struktur-data')['id'];
$quiz->submit(1, $attempt);
$quiz->submit(1, $attempt);
$quest = new QuestService($db);
if ($quest->totalXp(1) !== 25 || $quest->totalXp(2) !== 0) {
    throw new RuntimeException('XP totals must use the rewards owned by each student.');
}
$rows = $quest->visible(1);
if (count($rows) !== 1 || (int) $rows[0]['progress'] !== 1 || (int) $rows[0]['awarded_xp'] !== 25 || $rows[0]['quiz_slug'] !== 'struktur-data') {
    throw new RuntimeException('A completed attempt should award quest XP exactly once.');
}
if ((int) $db->query("SELECT count(*) FROM eduquest.quest_rewards WHERE quest_id = $questId AND student_id = 1")->fetchColumn() !== 1) {
    throw new RuntimeException('Repeated submission must not duplicate rewards.');
}
if ((int) $db->query("SELECT count(*) FROM eduquest.quest_rewards WHERE quest_id = $questId AND student_id = 2")->fetchColumn() !== 0) {
    throw new RuntimeException('Rewards must belong only to the student who completed the activity.');
}
$class = (int) $db->query("INSERT INTO eduquest.classes (name, academic_year) VALUES ('Quest B', '2026/2027') RETURNING id")->fetchColumn();
$db->exec("INSERT INTO eduquest.quests (title, quiz_id, starts_at, ends_at, target, xp, audience) VALUES ('Kelas B', $quizId, clock_timestamp() - interval '1 hour', clock_timestamp() + interval '1 hour', 2, 50, 'selected_classes')");
$classQuest = (int) $db->query("SELECT id FROM eduquest.quests WHERE title = 'Kelas B'")->fetchColumn();
$db->exec("INSERT INTO eduquest.quest_classes VALUES ($classQuest, $class)");
if (count($quest->visible(1)) !== 1) {
    throw new RuntimeException('A student cannot see a quest for another class.');
}
$db->exec("INSERT INTO eduquest.class_students VALUES ($class, 1)");
$visible = $quest->visible(1);
if (count($visible) !== 2 || (int) $visible[1]['progress'] !== 0) {
    throw new RuntimeException('Attempts completed before class membership must not count toward a class quest.');
}
$secondAttempt = (int) $quiz->start(1, 'struktur-data')['id'];
$quiz->submit(1, $secondAttempt);
$quiz->submit(1, $secondAttempt);
$visible = $quest->visible(1);
if ((int) $visible[1]['progress'] !== 1 || (int) $visible[1]['awarded_xp'] !== 0) {
    throw new RuntimeException('Eligible activities count once and must reach the target before reward.');
}
$db->exec("DELETE FROM eduquest.class_students WHERE class_id = $class AND student_id = 1");
if (count($quest->visible(1)) !== 1) {
    throw new RuntimeException('Class quest must disappear when class access is revoked.');
}
$db->exec("UPDATE eduquest.quizzes SET audience = 'selected_classes' WHERE id = $quizId");
$db->exec("INSERT INTO eduquest.quiz_classes VALUES ($quizId, $class)");
if ($quest->visible(1) !== []) {
    throw new RuntimeException('A quest must not advertise a quiz that the student cannot access.');
}
$teacher = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('guru-quest', 'Guru Quest', 'teacher', true, 'fixture') RETURNING id")->fetchColumn();
$db->exec("INSERT INTO eduquest.class_teachers VALUES ($class, $teacher)");
$db->exec("UPDATE eduquest.quizzes SET teacher_owner = $teacher WHERE id = $quizId");
$data = ['title' => 'Quest kelas', 'quiz_id' => (string) $quizId, 'starts_at' => '2026-11-25T08:00', 'ends_at' => '2026-11-26T08:00', 'target' => '1', 'xp' => '20', 'class_id' => (string) $class];
$created = $quest->create($teacher, $data);
if ((int) $db->query("SELECT count(*) FROM eduquest.quest_classes WHERE quest_id = $created AND class_id = $class")->fetchColumn() !== 1) {
    throw new RuntimeException('A teacher quest must target their assigned class.');
}
$admin = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('admin-quest', 'Admin Quest', 'admin', true, 'fixture') RETURNING id")->fetchColumn();
try {
    $quest->archive($teacher, $questId);
    throw new RuntimeException('Teachers cannot archive quests they did not create.');
} catch (QuizProblem $problem) {
    if ($problem->status !== 404) { throw $problem; }
}
$other = database();
$other->exec("SET lock_timeout = '250ms'");
$db->beginTransaction();
$db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
try {
    (new QuestService($other))->archive($teacher, $created);
    throw new RuntimeException('Archiving must wait for an in-flight quiz transaction.');
} catch (PDOException $error) {
    if ($error->getCode() !== '55P03') { throw $error; }
} finally {
    $db->rollBack();
}
$quest->archive($teacher, $created);
$quest->archive($admin, $questId);
if ((int) $db->query("SELECT count(*) FROM eduquest.admin_changes WHERE actor_id = $teacher AND action IN ('quest_create', 'quest_archive') AND target = '$created'")->fetchColumn() !== 2) {
    throw new RuntimeException('Quest creation and archival must leave an audit trail.');
}
if ($quest->totalXp(1) !== 25 || (int) $db->query("SELECT count(*) FROM eduquest.quest_events WHERE quest_id = $questId")->fetchColumn() !== 2) {
    throw new RuntimeException('Archiving a quest must preserve earned XP and its activity history.');
}
foreach ([$data + ['unused' => ''], array_replace($data, ['class_id' => '0'])] as $i => $invalid) {
    if ($i === 0) { $db->exec("DELETE FROM eduquest.class_teachers WHERE class_id = $class AND teacher_id = $teacher"); }
    try {
        $quest->create($teacher, $invalid);
        throw new RuntimeException('A teacher cannot publish outside their assigned class.');
    } catch (QuizProblem $problem) {
        if (!in_array($problem->status, [403, 422], true)) { throw $problem; }
    }
}
$reviewQuiz = (int) $db->query("SELECT id FROM eduquest.quizzes WHERE slug = 'http-api'")->fetchColumn();
$reviewQuest = (int) $db->query("INSERT INTO eduquest.quests (title, quiz_id, starts_at, ends_at, target, xp, kind) VALUES ('Baca pembahasan', $reviewQuiz, clock_timestamp() - interval '1 hour', clock_timestamp() + interval '1 hour', 1, 15, 'review') RETURNING id")->fetchColumn();
$reviewAttempt = (int) $quiz->start(2, 'http-api')['id'];
$quiz->submit(2, $reviewAttempt);
$reviewRows = $quest->visible(2);
if (count($reviewRows) !== 1 || (int) $reviewRows[0]['result_id'] !== $reviewAttempt) {
    throw new RuntimeException('Review quests should link to the student’s own completed result.');
}
$quiz->review(2, $reviewAttempt);
if ($quest->totalXp(2) !== 0) { throw new RuntimeException('Opening review must not count as confirming it has been read.'); }
try {
    $quiz->confirmReview(1, $reviewAttempt);
    throw new RuntimeException('A different student must not confirm another student’s review.');
} catch (QuizProblem $problem) { if ($problem->status !== 404) { throw $problem; } }
$quiz->confirmReview(2, $reviewAttempt);
$quiz->confirmReview(2, $reviewAttempt);
if ($quest->totalXp(2) !== 15 || (int) $db->query("SELECT count(*) FROM eduquest.quest_events WHERE quest_id = $reviewQuest")->fetchColumn() !== 1) {
    throw new RuntimeException('Confirming review should award its quest exactly once.');
}
$expired = (int) $db->query("INSERT INTO eduquest.quests (title, quiz_id, starts_at, ends_at, target, xp, kind) VALUES ('Quest berakhir', $reviewQuiz, clock_timestamp() - interval '2 hours', clock_timestamp() - interval '1 hour', 1, 50, 'review') RETURNING id")->fetchColumn();
$quiz->confirmReview(2, $reviewAttempt);
if ($quest->totalXp(2) !== 15 || (int) $db->query("SELECT count(*) FROM eduquest.quest_events WHERE quest_id = $expired")->fetchColumn() !== 0) {
    throw new RuntimeException('A review confirmation after a quest ends cannot earn its reward.');
}
$answerQuest = (int) $db->query("INSERT INTO eduquest.quests (title, quiz_id, starts_at, ends_at, target, xp, kind) VALUES ('Jawab dua soal', $reviewQuiz, clock_timestamp() - interval '1 hour', clock_timestamp() + interval '1 hour', 2, 10, 'answers') RETURNING id")->fetchColumn();
$answerAttempt = (int) $quiz->start(2, 'http-api')['id'];
$questions = $quiz->attempt(2, $answerAttempt)['questions'];
$quiz->save(2, $answerAttempt, (int) $questions[0]['id'], (int) $questions[0]['options'][0]['id'], false);
$quiz->save(2, $answerAttempt, (int) $questions[0]['id'], (int) $questions[0]['options'][1]['id'], false);
$quiz->save(2, $answerAttempt, (int) $questions[1]['id'], (int) $questions[1]['options'][0]['id'], false);
$db->exec("UPDATE eduquest.answers SET answer_updated_at = clock_timestamp() - interval '2 hours' WHERE attempt_id = $answerAttempt AND question_id = " . (int) $questions[0]['id']);
$quiz->submit(2, $answerAttempt);
$quiz->submit(2, $answerAttempt);
if ($quest->totalXp(2) !== 15 || (int) $db->query("SELECT sum(amount) FROM eduquest.quest_events WHERE quest_id = $answerQuest AND student_id = 2")->fetchColumn() !== 1) {
    throw new RuntimeException('Only distinct answers saved during the active quest count towards its target.');
}
$rows = $quest->visible(2);
$answerRow = array_values(array_filter($rows, fn (array $row): bool => (int) $row['id'] === $answerQuest))[0];
if (!array_key_exists('can_start', $answerRow) || $answerRow['can_start'] || (int) $answerRow['progress'] !== 1) {
    throw new RuntimeException('An unfinished quest should identify when a student has exhausted the quiz attempts.');
}
$previous = (int) $quiz->start(2, 'sql-relasional')['id'];
$quiz->submit(2, $previous);
$limitedQuiz = (int) $db->query("SELECT id FROM eduquest.quizzes WHERE slug = 'sql-relasional'")->fetchColumn();
$limitedQuest = (int) $db->query("INSERT INTO eduquest.quests (title, quiz_id, starts_at, ends_at, target, xp) VALUES ('Quest melebihi sisa', $limitedQuiz, clock_timestamp() - interval '1 hour', clock_timestamp() + interval '1 hour', 3, 5) RETURNING id")->fetchColumn();
$limitedRow = array_values(array_filter($quest->visible(2), fn (array $row): bool => (int) $row['id'] === $limitedQuest))[0];
if (!array_key_exists('target_reachable', $limitedRow) || $limitedRow['target_reachable']) {
    throw new RuntimeException('A quest must identify a target exceeding the remaining quiz attempts.');
}
echo "PASS: quest progress and XP are awarded once to the attempt owner.\n";
