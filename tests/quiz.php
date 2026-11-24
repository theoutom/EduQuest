<?php

declare(strict_types=1);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}

function rejects(callable $operation, int $status): bool
{
    try {
        $operation();
    } catch (QuizProblem $problem) {
        return $problem->status === $status;
    }
    return false;
}

$root = dirname(__DIR__);
check(is_file("$root/app/QuizService.php"), 'Quiz service is available');
require "$root/app/QuizService.php";
require "$root/app/database.php";

$db = database();
$quiz = new QuizService($db);
$alice = 1;
$bob = 2;

$items = $quiz->catalog($alice, 'struktur', 'pemrograman', 'dasar');
check(count($items) === 1 && $items[0]['slug'] === 'struktur-data', 'Catalogue combines title, topic and level filters');
check($quiz->catalog($alice, '%', '', '') === [], 'Search treats wildcard input as literal text');
$attempt = $quiz->start($alice, 'struktur-data');
$id = (int) $attempt['id'];
check((int) $quiz->start($alice, 'struktur-data')['id'] === $id, 'Start resumes the same active attempt');
$open = $quiz->attempt($alice, $id);
$first = $open['questions'][0];
$second = $open['questions'][1];
check(!array_key_exists('explanation', $first) && !array_key_exists('is_correct', $first['options'][0]), 'Active attempt never returns answer key or explanation');
check(rejects(fn () => $quiz->attempt($bob, $id), 404), 'A different student cannot open the attempt');
check(rejects(fn () => $quiz->review($alice, $id), 409), 'Review waits for finalization');
check(rejects(fn () => $quiz->save($bob, $id, (int) $first['id'], (int) $first['options'][0]['id'], true), 404), 'A different student cannot save an answer');
check(rejects(fn () => $quiz->save($alice, $id, (int) $first['id'], (int) $second['options'][0]['id'], false), 422), 'An option must belong to the same question');
$quiz->save($alice, $id, (int) $first['id'], (int) $first['options'][0]['id'], true);
$quiz->save($alice, $id, (int) $second['id'], (int) $second['options'][1]['id'], false);
$resumed = $quiz->attempt($alice, $id);
check((int) $resumed['questions'][0]['selected_id'] === (int) $first['options'][0]['id'] && $resumed['questions'][0]['doubtful'] && $resumed['deadline'] === $open['deadline'], 'Reload preserves selection, doubtful flag and deadline');

$result = $quiz->submit($alice, $id);
check($result['score'] === 33 && $result['correct'] === 1 && $result['wrong'] === 1 && $result['blank'] === 1 && !$result['passed'], 'Scoring rounds 1 of 3 and reports wrong and blank separately');
check($quiz->submit($alice, $id) === $result, 'Submitting twice returns the same result');
$review = $quiz->review($alice, $id);
check($review['questions'][0]['explanation'] !== '' && $review['questions'][0]['correct_label'] === 'A' && $review['questions'][2]['outcome'] === 'kosong', 'Review exposes the official key and explanation after submission');
check(rejects(fn () => $quiz->review($bob, $id), 404), 'Review is accessible only to the owner');
check(rejects(fn () => $quiz->save($alice, $id, (int) $first['id'], null, false), 409), 'An already submitted attempt cannot be overwritten');

$retry = (int) $quiz->start($alice, 'struktur-data')['id'];
$quiz->save($alice, $retry, (int) $first['id'], (int) $first['options'][0]['id'], false);
$db->prepare("UPDATE eduquest.attempts SET started_at = clock_timestamp() - interval '20 minutes', deadline = clock_timestamp() - interval '1 second' WHERE id = ?")->execute([$retry]);
check(rejects(fn () => $quiz->save($alice, $retry, (int) $first['id'], null, false), 409), 'Late writes are rejected');
check($quiz->review($alice, $retry)['score'] === 33, 'Expiry finalizes previously saved answers');
check(rejects(fn () => $quiz->start($alice, 'struktur-data'), 409), 'Attempt limits apply after the final retry');
check((int) $quiz->start($bob, 'struktur-data')['id'] !== $retry, 'The second demo account has an independent attempt budget');

$slow = $quiz->start($bob, 'http-api');
$slowId = (int) $slow['id'];
$question = $quiz->attempt($bob, $slowId)['questions'][0];
$db->exec(<<<'SQL'
    CREATE FUNCTION eduquest.delay_answer_write() RETURNS trigger LANGUAGE plpgsql AS $$
    BEGIN
        PERFORM pg_sleep(GREATEST(0, extract(epoch FROM (
            (SELECT deadline FROM eduquest.attempts WHERE id = NEW.attempt_id) - clock_timestamp()
        )) + 0.05));
        RETURN NEW;
    END;
    $$;
    CREATE TRIGGER delay_answer_write BEFORE INSERT ON eduquest.answers
        FOR EACH ROW EXECUTE FUNCTION eduquest.delay_answer_write();
    SQL);
try {
    $db->prepare("UPDATE eduquest.attempts SET deadline = clock_timestamp() + interval '1 second' WHERE id = ?")->execute([$slowId]);
    $late = rejects(fn () => $quiz->save($bob, $slowId, (int) $question['id'], (int) $question['options'][1]['id'], false), 409);
    $timedOut = $quiz->review($bob, $slowId);
} finally {
    $db->exec('DROP TRIGGER delay_answer_write ON eduquest.answers; DROP FUNCTION eduquest.delay_answer_write()');
}
$ordinalQuiz = $db->query(<<<'SQL'
    INSERT INTO eduquest.quizzes (slug, title, summary, topic, level, attempt_limit)
    VALUES ('ordinal-fixture', 'Ordinal fixture', 'Fixture with gaps in stored positions.', 'basis-data', 'dasar', 1) RETURNING id
    SQL)->fetchColumn();
$statement = $db->prepare('INSERT INTO eduquest.quiz_versions (quiz_id, version, duration_seconds, passing_score) VALUES (?, 1, 600, 67) RETURNING id');
$statement->execute([$ordinalQuiz]);
$ordinalVersion = $statement->fetchColumn();
try {
    $db->prepare('UPDATE eduquest.quiz_versions SET published_at = clock_timestamp() WHERE id = ?')->execute([$ordinalVersion]);
    $emptyRefused = false;
} catch (PDOException $exception) {
    $emptyRefused = $exception->getCode() === '23514';
}
check($emptyRefused, 'PostgreSQL rejects direct publication of a quiz without questions');
$statement = $db->prepare("INSERT INTO eduquest.questions (version_id, position, stem, explanation) VALUES (?, 11, 'Ordinal?', 'Fourth choice.') RETURNING id");
$statement->execute([$ordinalVersion]);
$ordinalQuestion = $statement->fetchColumn();
$statement = $db->prepare(<<<'SQL'
    INSERT INTO eduquest.options (question_id, position, label, is_correct)
    SELECT ?, v.position, v.label, v.correct FROM (VALUES (1, 'A', false), (3, 'B', false), (4, 'C', false), (5, 'D', true))
    AS v(position, label, correct)
    SQL);
$statement->execute([$ordinalQuestion]);
$db->prepare('UPDATE eduquest.quiz_versions SET published_at = clock_timestamp() WHERE id = ?')->execute([$ordinalVersion]);
$sparseId = (int) $quiz->start($alice, 'ordinal-fixture')['id'];
$sparse = $quiz->attempt($alice, $sparseId);
$quiz->save($alice, $sparseId, (int) $sparse['questions'][0]['id'], (int) $sparse['questions'][0]['options'][3]['id'], false);
$quiz->submit($alice, $sparseId);
$mapped = $quiz->review($alice, $sparseId)['questions'][0];
check($mapped['position'] === 1 && $mapped['correct_label'] === 'D' && $mapped['selected_label'] === 'D', 'Review uses the same ordinal numbering as the exercise');
check($late && $timedOut['score'] === 0 && $timedOut['blank'] === 3, 'A blocked answer write cannot commit after the deadline');
try {
    $db->prepare("UPDATE eduquest.questions SET explanation = 'Changed' WHERE id = ?")->execute([$ordinalQuestion]);
    $immutable = false;
} catch (PDOException $exception) {
    $immutable = $exception->getCode() === '23514';
}
check($immutable, 'PostgreSQL rejects direct edits to published questions');

$streakStudent = (int) $db->query(<<<'SQL'
    INSERT INTO eduquest.students (id, username, name, enabled) OVERRIDING SYSTEM VALUE
    VALUES (-1, 'streak-fixture', 'Fixture', false) RETURNING id
    SQL)->fetchColumn();
check($quiz->streak($streakStudent) === 0, 'Students start with no streak');
$dates = $db->prepare(<<<'SQL'
    INSERT INTO eduquest.attempts (student_id, quiz_id, version_id, started_at, deadline, finished_at, score)
    SELECT ?, v.quiz_id, v.id, series.completed - interval '1 minute', series.completed, series.completed, 100
    FROM (SELECT id, quiz_id FROM eduquest.quiz_versions WHERE published_at IS NOT NULL ORDER BY id LIMIT 1) v
    CROSS JOIN LATERAL (
        SELECT ((statement_timestamp() AT TIME ZONE 'Asia/Jakarta')::date - n + time '00:05') AT TIME ZONE 'Asia/Jakarta' AS completed
        FROM (VALUES (1), (1), (2), (4)) days(n)
    ) series
    SQL);
$dates->execute([$streakStudent]);
check($quiz->streak($streakStudent) === 2, 'Yesterday keeps the WIB streak alive and duplicate attempts count once');
