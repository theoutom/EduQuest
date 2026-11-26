<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/QuizService.php';
require dirname(__DIR__) . '/app/MasteryService.php';
require dirname(__DIR__) . '/app/ClassService.php';
require dirname(__DIR__) . '/app/AdminStore.php';

$db = database();
$db->exec("UPDATE eduquest.students SET enabled = true, password_hash = 'fixture' WHERE id IN (1, 2)");
$teacher = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('guru-mastery', 'Guru Mastery', 'teacher', true, 'fixture') RETURNING id")->fetchColumn();
$classA = (int) $db->query("INSERT INTO eduquest.classes (name, academic_year) VALUES ('Mastery A', '2026/2027') RETURNING id")->fetchColumn();
$classB = (int) $db->query("INSERT INTO eduquest.classes (name, academic_year) VALUES ('Mastery B', '2026/2027') RETURNING id")->fetchColumn();
$db->exec("INSERT INTO eduquest.class_teachers VALUES ($classA, $teacher); INSERT INTO eduquest.class_students VALUES ($classA, 1), ($classB, 2)");
$quiz = (int) $db->query("SELECT id FROM eduquest.quizzes WHERE slug = 'struktur-data'")->fetchColumn();
$db->exec("UPDATE eduquest.quizzes SET audience='selected_classes', teacher_owner=$teacher WHERE id=$quiz; INSERT INTO eduquest.quiz_classes VALUES ($quiz, $classA)");
$service = new MasteryService($db);
$input = ['title' => 'Dasar', 'description' => 'Pahami dasar', 'topic' => 'pemrograman', 'quiz_id' => (string) $quiz, 'class_id' => (string) $classA, 'prerequisites' => []];
$root = $service->save($teacher, $input);
$child = $service->save($teacher, array_replace($input, ['title' => 'Lanjutan', 'prerequisites' => [(string) $root]]));
$empty = $service->save($teacher, array_replace($input, ['title' => 'Belum siap', 'quiz_id' => '0']));
$admin = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('admin-mastery', 'Admin Mastery', 'admin', true, 'fixture') RETURNING id")->fetchColumn();
$otherNode = $service->save($admin, array_replace($input, ['title' => 'Kelas lain', 'class_id' => (string) $classB, 'quiz_id' => '0']));
$globalNode = $service->save($admin, array_replace($input, ['title' => 'Untuk semua', 'class_id' => '0', 'quiz_id' => '0']));
$sharedNode = $service->save($admin, array_replace($input, ['title' => 'Dibuat admin', 'class_id' => (string) $classA, 'quiz_id' => '0']));
$teacherOptions = array_map('intval', array_column($service->prerequisiteOptions($teacher), 'id'));
if (!in_array($globalNode, $teacherOptions, true) || !in_array($sharedNode, $teacherOptions, true) || in_array($otherNode, $teacherOptions, true)) {
    throw new RuntimeException('Teachers may select global and assigned-class prerequisites but not another class.');
}
if (in_array($sharedNode, array_map('intval', array_column($service->manage($teacher, true), 'id')), true)) {
    throw new RuntimeException('Prerequisite visibility must not grant permission to edit another creator’s node.');
}
try {
    $service->save($teacher, array_replace($input, ['prerequisites' => [(string) $otherNode]]));
    throw new RuntimeException('A prerequisite in a different class must be rejected.');
} catch (QuizProblem $problem) { if ($problem->status !== 422) { throw $problem; } }
$byId = static fn (array $rows): array => array_column($rows, null, 'id');
$nodes = $byId($service->visible(1));
if ($nodes[$root]['status'] !== 'available' || $nodes[$child]['status'] !== 'locked' || $nodes[$empty]['status'] !== 'unavailable' || count($service->visible(2)) !== 2) {
    throw new RuntimeException('Mastery visibility and prerequisites must respect class membership.');
}
foreach ([$root => ['prerequisites' => [(string) $child]], 0 => ['class_id' => (string) $classB], -1 => ['class_id' => '0']] as $id => $change) {
    try {
        $data = array_replace($input, $change);
        if ($id > 0) { $data['node_id'] = (string) $id; }
        $service->save($teacher, $data);
        throw new RuntimeException('Cyclic or unauthorized mastery rule must be rejected.');
    } catch (QuizProblem $problem) {
        if (!in_array($problem->status, [403, 404, 422], true)) { throw $problem; }
    }
}
$version = (int) $db->query("SELECT id FROM eduquest.quiz_versions WHERE quiz_id = $quiz AND published_at IS NOT NULL ORDER BY version DESC LIMIT 1")->fetchColumn();
$failed = (int) $db->query("INSERT INTO eduquest.attempts (student_id, quiz_id, version_id, started_at, deadline, finished_at, score) VALUES (1, $quiz, $version, now()-interval '1 minute', now()+interval '1 minute', now(), 0) RETURNING id")->fetchColumn();
$db->beginTransaction(); $service->recordAttempt($failed); $db->commit();
if ($byId($service->visible(1))[$root]['status'] !== 'available') { throw new RuntimeException('A score below the passing threshold must not grant mastery.'); }
$attempt = (int) $db->query("INSERT INTO eduquest.attempts (student_id, quiz_id, version_id, started_at, deadline, finished_at, score) VALUES (1, $quiz, $version, now()-interval '1 minute', now()+interval '1 minute', now(), 100) RETURNING id")->fetchColumn();
$db->beginTransaction();
$service->recordAttempt($attempt);
$service->recordAttempt($attempt);
$db->commit();
$nodes = $byId($service->visible(1));
if ($nodes[$root]['status'] !== 'mastered' || $nodes[$child]['status'] !== 'mastered') {
    throw new RuntimeException('The same passing result must satisfy dependent nodes using the same quiz once prerequisites are met.');
}
$service->save($teacher, array_replace($input, ['node_id' => (string) $root, 'title' => 'Dasar revisi']));
$nodes = $byId($service->visible(1));
if ($nodes[$root]['status'] !== 'mastered' || (int) $db->query("SELECT count(*) FROM eduquest.mastery_rules WHERE node_id = $root")->fetchColumn() !== 2 || (int) $db->query("SELECT count(*) FROM eduquest.mastery_awards WHERE node_id = $root AND student_id = 1")->fetchColumn() !== 1) {
    throw new RuntimeException('A revision must preserve the version and evidence of previous mastery.');
}
$next = (int) $db->query("INSERT INTO eduquest.attempts (student_id, quiz_id, version_id, started_at, deadline, finished_at, score) VALUES (1, $quiz, $version, now()-interval '1 minute', now()+interval '1 minute', now(), 100) RETURNING id")->fetchColumn();
$db->beginTransaction(); $service->recordAttempt($next); $db->commit();
if ($byId($service->visible(1))[$child]['status'] !== 'mastered') { throw new RuntimeException('A later passing attempt should master an unlocked node.'); }
$historicalQuiz = (int) $db->query("SELECT id FROM eduquest.quizzes WHERE slug = 'sql-relasional'")->fetchColumn();
$historicalVersion = (int) $db->query("SELECT id FROM eduquest.quiz_versions WHERE quiz_id = $historicalQuiz AND published_at IS NOT NULL ORDER BY version DESC LIMIT 1")->fetchColumn();
$historicalAttempt = (int) $db->query("INSERT INTO eduquest.attempts (student_id, quiz_id, version_id, started_at, deadline, finished_at, score) VALUES (1, $historicalQuiz, $historicalVersion, now()-interval '1 hour', now()-interval '30 minutes', now()-interval '31 minutes', 100) RETURNING id")->fetchColumn();
$historicalNode = $service->save($admin, array_replace($input, ['title' => 'Bukti lama', 'topic' => 'basis-data', 'quiz_id' => (string) $historicalQuiz]));
if ($byId($service->visible(1))[$historicalNode]['status'] !== 'mastered') { throw new RuntimeException('A qualifying saved result must count for a node created later.'); }
$service->visible(1);
if ((int) $db->query("SELECT count(*) FROM eduquest.mastery_awards WHERE node_id = $historicalNode AND student_id = 1 AND attempt_id = $historicalAttempt")->fetchColumn() !== 1) {
    throw new RuntimeException('Historical result reconciliation must preserve one idempotent evidence record.');
}
$historicalPeerAttempt = (int) $db->query("INSERT INTO eduquest.attempts (student_id, quiz_id, version_id, started_at, deadline, finished_at, score) VALUES (2, $historicalQuiz, $historicalVersion, now()-interval '1 hour', now()-interval '30 minutes', now()-interval '31 minutes', 100) RETURNING id")->fetchColumn();
$peerNode = $service->save($admin, array_replace($input, ['title' => 'Tidak berhak', 'topic' => 'basis-data', 'class_id' => (string) $classB, 'quiz_id' => (string) $historicalQuiz]));
$db->exec("UPDATE eduquest.quizzes SET audience = 'selected_classes' WHERE id = $historicalQuiz; INSERT INTO eduquest.quiz_classes VALUES ($historicalQuiz, $classA)");
$peerRows = $byId($service->visible(2));
if ($peerRows[$peerNode]['status'] !== 'unavailable' || (int) $db->query("SELECT count(*) FROM eduquest.mastery_awards WHERE node_id = $peerNode AND student_id = 2")->fetchColumn() !== 0) {
    throw new RuntimeException('Historical evidence must not be granted while the student cannot access the related quiz.');
}
$service->save($teacher, array_replace($input, ['node_id' => (string) $empty, 'title' => 'Butuh pembaruan']));
$editor = new AdminStore($db, $teacher);
$editor->changeQuiz($quiz, 'draft', [], true);
$draft = (string) $editor->quiz($quiz)['draft']['id'];
$editor->changeQuiz($quiz, 'publish', ['draft_id' => $draft], true);
$nodes = $byId($service->visible(1));
$newStudent = (int) $db->query("INSERT INTO eduquest.students (username, name, role, enabled, password_hash) VALUES ('siswa-mastery-baru', 'Siswa Baru', 'student', true, 'fixture') RETURNING id")->fetchColumn();
$db->exec("INSERT INTO eduquest.class_students VALUES ($classA, $newStudent)");
$newStudentNodes = $byId($service->visible($newStudent));
if ($newStudentNodes[$empty]['status'] !== 'unavailable' || $nodes[$empty]['status'] !== 'unavailable' || $nodes[$root]['status'] !== 'mastered'
    || (int) $db->query("SELECT count(*) FROM eduquest.mastery_awards WHERE node_id = $empty AND student_id = 1")->fetchColumn() !== 0) {
    throw new RuntimeException('A newer quiz version must require a rule update without erasing past mastery.');
}
$db->beginTransaction();
$db->query("SELECT pg_advisory_xact_lock(476152003, $quiz)");
$worker = proc_open([PHP_BINARY, '-d', 'extension_dir=' . ini_get('extension_dir'), '-d', 'extension=pdo_pgsql',
    __DIR__ . '/mastery-lock-worker.php', (string) $teacher, (string) $quiz, (string) $classA],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($worker)) { $db->rollBack(); throw new RuntimeException('Cannot start lock test worker.'); }
fclose($pipes[0]);
$waiting = false;
for ($i = 0; $i < 100; $i++) {
    if ((int) $db->query("SELECT count(*) FROM pg_locks WHERE locktype = 'advisory' AND classid = 476152003 AND objid = $quiz AND NOT granted")->fetchColumn() > 0) { $waiting = true; break; }
    usleep(50000);
}
$safeOrder = $waiting && (bool) $db->query('SELECT pg_try_advisory_xact_lock(476152006)')->fetchColumn();
$db->commit();
$workerOutput = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
$workerStatus = proc_close($worker);
if (!$safeOrder || $workerStatus !== 0) { throw new RuntimeException('Mastery must wait for the quiz lock before taking its graph lock: ' . $workerOutput); }
echo "PASS: class-scoped mastery, prerequisites, versioned rules, and earned evidence.\n";
