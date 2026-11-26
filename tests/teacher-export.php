<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/QuizService.php';
require dirname(__DIR__) . '/app/ClassService.php';
require dirname(__DIR__) . '/app/TeacherExport.php';

$db = database();
$db->exec("UPDATE eduquest.students SET enabled = true, password_hash = 'fixture' WHERE id IN (1, 2)");
$db->exec("UPDATE eduquest.students SET name = 'Siswa A' WHERE id = 1");
$db->exec("UPDATE eduquest.students SET name = 'Siswa B' WHERE id = 2");
$teacher = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('guru-ekspor', 'Guru Ekspor', 'teacher', true, 'fixture') RETURNING id")->fetchColumn();
$a = (int) $db->query("INSERT INTO eduquest.classes (name, academic_year) VALUES ('Ekspor A', '2026/2027') RETURNING id")->fetchColumn();
$b = (int) $db->query("INSERT INTO eduquest.classes (name, academic_year) VALUES ('Ekspor B', '2026/2027') RETURNING id")->fetchColumn();
$db->exec("INSERT INTO eduquest.class_students VALUES ($a, 1), ($b, 2)");
$db->exec("INSERT INTO eduquest.class_teachers VALUES ($a, $teacher)");
$quiz = (int) $db->query("SELECT id FROM eduquest.quizzes WHERE slug = 'struktur-data'")->fetchColumn();
$db->exec("UPDATE eduquest.quizzes SET teacher_owner = $teacher, audience = 'selected_classes' WHERE id = $quiz");
$db->exec("INSERT INTO eduquest.quiz_classes VALUES ($quiz, $a)");
$db->exec("INSERT INTO eduquest.attempts (student_id, quiz_id, version_id, started_at, deadline, finished_at, score)
    SELECT s.id, q.id, v.id, clock_timestamp() - interval '2 minutes', clock_timestamp() + interval '3 minutes', clock_timestamp(), 75
    FROM eduquest.students s CROSS JOIN eduquest.quizzes q JOIN eduquest.quiz_versions v ON v.quiz_id = q.id WHERE s.id IN (1, 2) AND q.id = $quiz");
$service = new TeacherExport($db);
$scores = $service->document($teacher, $quiz, 'scores');
if (count($scores['rows']) !== 1 || !in_array('Siswa A', $scores['rows'][0], true) || in_array('Siswa B', $scores['rows'][0], true)) {
    throw new RuntimeException('Score export must include only students currently assigned to the teacher class.');
}
$questions = $service->document($teacher, $quiz, 'questions');
if (count($questions['rows']) < 1 || !str_contains(implode(' ', $questions['rows'][0]), 'sisa pembagian')) {
    throw new RuntimeException('Question export must include published question and explanation.');
}
$db->exec("DELETE FROM eduquest.class_teachers WHERE class_id = $a AND teacher_id = $teacher");
try { $service->document($teacher, $quiz, 'scores'); throw new RuntimeException('Revoked teacher access leaked export.'); }
catch (QuizProblem $problem) { if ($problem->status !== 404) { throw $problem; } }
echo "PASS: teacher export is class scoped and revoked access is rejected.\n";
