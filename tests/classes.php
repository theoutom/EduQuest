<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/QuizService.php';
require dirname(__DIR__) . '/app/ClassService.php';

function expectClassProblem(callable $operation, int $status): void
{
    try {
        $operation();
    } catch (QuizProblem $problem) {
        if ($problem->status === $status) {
            return;
        }
        throw $problem;
    }
    throw new RuntimeException("Expected class access error $status.");
}

$db = database();
$db->exec("UPDATE eduquest.students SET enabled = true, password_hash = 'fixture' WHERE id IN (1, 2)");
$classes = new ClassService($db);
$a = $classes->create('XI-PPLG-A', '2026/2027');
$b = $classes->create('XI-PPLG-B', '2026/2027');
$classes->assignStudent($a, 1);
$classes->assignStudent($b, 2);
$other = database();
$other->exec("SET lock_timeout = '250ms'");
$db->beginTransaction();
$db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
try {
    (new ClassService($other))->removeStudent($b, 2);
    throw new RuntimeException('Concurrent class revocation did not wait for an active quiz transaction.');
} catch (PDOException $error) {
    if ($error->getCode() !== '55P03') {
        throw $error;
    }
} finally {
    $db->rollBack();
}
$teacher = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('guru-lock', 'Guru Lock', 'teacher', false, NULL) RETURNING id")->fetchColumn();
$db->exec("INSERT INTO eduquest.class_teachers (class_id, teacher_id) VALUES ($b, $teacher)");
$db->beginTransaction();
$db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
try {
    (new ClassService($other))->removeTeacher($b, $teacher);
    throw new RuntimeException('Concurrent teacher revocation did not wait for an active operation.');
} catch (PDOException $error) {
    if ($error->getCode() !== '55P03') {
        throw $error;
    }
} finally {
    $db->rollBack();
}
$db->beginTransaction();
$db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
try {
    $other->exec("UPDATE eduquest.students SET role = 'student' WHERE id = $teacher");
    throw new RuntimeException('Concurrent role change did not wait for an active operation.');
} catch (PDOException $error) {
    if ($error->getCode() !== '55P03') {
        throw $error;
    }
} finally {
    $db->rollBack();
}
$other->exec("UPDATE eduquest.students SET role = 'student' WHERE id = $teacher");
if ((int) $db->query("SELECT count(*) FROM eduquest.class_teachers WHERE teacher_id = $teacher")->fetchColumn() !== 0) {
    throw new RuntimeException('Role change retained a teacher assignment.');
}
$quizId = (int) $db->query("SELECT id FROM eduquest.quizzes WHERE slug = 'struktur-data'")->fetchColumn();
$classes->setQuizAudience($quizId, 'selected_classes', [$b]);
$quiz = new QuizService($db);
expectClassProblem(fn () => $quiz->overview(1, 'struktur-data'), 404);
if ($quiz->overview(2, 'struktur-data')['id'] !== $quizId) {
    throw new RuntimeException('Class member cannot access an assigned quiz.');
}
expectClassProblem(fn () => $quiz->setBookmark(1, 'struktur-data', true), 404);
$attempt = $quiz->start(2, 'struktur-data');
$attemptId = (int) $attempt['id'];
$question = $quiz->attempt(2, $attemptId)['questions'][0];
$db->exec("DELETE FROM eduquest.class_students WHERE class_id = $b AND student_id = 2");
expectClassProblem(fn () => $quiz->attempt(2, $attemptId), 404);
expectClassProblem(fn () => $quiz->save(2, $attemptId, (int) $question['id'], (int) $question['options'][0]['id'], false), 404);
expectClassProblem(fn () => $quiz->submit(2, $attemptId), 404);
expectClassProblem(fn () => $quiz->review(2, $attemptId), 404);
$classes->assignStudent($b, 2);
$quiz->submit(2, $attemptId);
$owner = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('guru-pemilik', 'Guru Pemilik', 'teacher', true, 'fixture') RETURNING id")->fetchColumn();
$otherTeacher = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('guru-lain', 'Guru Lain', 'teacher', true, 'fixture') RETURNING id")->fetchColumn();
$classes->assignTeacher($b, $owner);
$classes->assignTeacher($b, $otherTeacher);
$db->exec("UPDATE eduquest.quizzes SET teacher_owner = $owner WHERE id = $quizId");
$db->exec("SET lock_timeout = '250ms'");
$other->beginTransaction();
$other->query('SELECT pg_advisory_xact_lock(476152004)');
try {
    $classes->teacherResults($owner, $quizId);
    throw new RuntimeException('Reading results must wait for concurrent access changes.');
} catch (PDOException $error) {
    if ($error->getCode() !== '55P03') { throw $error; }
} finally {
    $other->rollBack();
    $db->exec('RESET lock_timeout');
}
if (count($classes->teacherResults($owner, $quizId)) !== 1) {
    throw new RuntimeException('The assigned owner must see the results of their class quiz.');
}
if ($classes->teacherResults($otherTeacher, $quizId) !== [] || $classes->teacherResults(1, $quizId) !== []) {
    throw new RuntimeException('A class member or another teacher must not read an owned quiz’s results directly.');
}
$db->exec("UPDATE eduquest.students SET enabled = false WHERE id = $owner");
if ($classes->teacherResults($owner, $quizId) !== []) {
    throw new RuntimeException('A disabled teacher must not read results.');
}
$db->exec("UPDATE eduquest.students SET enabled = true WHERE id = $owner");
$db->exec("DELETE FROM eduquest.class_students WHERE class_id = $b AND student_id = 2");
if ($quiz->review(2, $attemptId)['score'] !== 0) {
    throw new RuntimeException('Completed results must remain accessible to their owner.');
}
$classes->setQuizAudience($quizId, 'all_classes', []);
if ($quiz->overview(1, 'struktur-data')['id'] !== $quizId) {
    throw new RuntimeException('All-class quiz is unavailable.');
}
echo "PASS: class membership gates quiz and all-class access.\n";
