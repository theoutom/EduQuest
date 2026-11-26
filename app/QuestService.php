<?php
declare(strict_types=1);

final class QuestService
{
    public function __construct(private PDO $db) {}

    public function totalXp(int $student): int
    {
        $query = $this->db->prepare('SELECT COALESCE(sum(xp), 0) FROM eduquest.quest_rewards WHERE student_id = ?');
        $query->execute([$student]);
        return (int) $query->fetchColumn();
    }

    public function create(int $actor, array $data): int
    {
        $title = trim(is_string($data['title'] ?? null) ? $data['title'] : '');
        $kind = $data['kind'] ?? 'quiz_finish';
        if (!is_string($kind) || !in_array($kind, ['quiz_finish', 'review', 'answers'], true)) {
            throw new QuizProblem(422, 'Jenis quest tidak valid.');
        }
        $integers = [];
        foreach (['quiz_id', 'target', 'xp', 'class_id'] as $field) {
            $value = $data[$field] ?? null;
            if (!is_string($value) || !preg_match('/^(0|[1-9][0-9]{0,8})$/D', $value)) {
                throw new QuizProblem(422, 'Isi semua pilihan quest dengan benar.');
            }
            $integers[$field] = (int) $value;
        }
        $tz = new DateTimeZone('Asia/Jakarta');
        $times = [];
        foreach (['starts_at', 'ends_at'] as $field) {
            $value = $data[$field] ?? null;
            $time = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $tz) : false;
            if (!$time || $time->format('Y-m-d\TH:i') !== $value) {
                throw new QuizProblem(422, 'Jadwal quest tidak valid.');
            }
            $times[$field] = $time;
        }
        if ($title === '' || mb_strlen($title) > 120 || $integers['target'] < 1 || $integers['target'] > 100
            || $integers['xp'] < 1 || $integers['xp'] > 1000 || $times['ends_at'] <= $times['starts_at']) {
            throw new QuizProblem(422, 'Periksa judul, target, XP dan jadwal quest.');
        }
        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
            $check = $this->db->prepare(<<<'SQL'
                SELECT s.role, q.audience, q.attempt_limit, q.teacher_owner,
                       (SELECT count(*) FROM eduquest.questions question WHERE question.version_id = (
                           SELECT version.id FROM eduquest.quiz_versions version WHERE version.quiz_id = q.id
                           AND version.published_at IS NOT NULL ORDER BY version.version DESC LIMIT 1
                       ))::int AS question_count,
                       EXISTS (SELECT 1 FROM eduquest.quiz_classes qc WHERE qc.quiz_id = q.id AND qc.class_id = ?) AS quiz_class,
                       EXISTS (SELECT 1 FROM eduquest.classes c WHERE c.id = ? AND c.archived_at IS NULL) AS active_class,
                       EXISTS (SELECT 1 FROM eduquest.class_teachers ct WHERE ct.teacher_id = s.id AND ct.class_id = ?) AS assigned
                FROM eduquest.students s CROSS JOIN eduquest.quizzes q
                WHERE s.id = ? AND s.enabled AND s.role IN ('teacher', 'admin')
                  AND q.id = ? AND q.archived_at IS NULL
                  AND EXISTS (SELECT 1 FROM eduquest.quiz_versions v WHERE v.quiz_id = q.id AND v.published_at IS NOT NULL)
                SQL);
            $class = $integers['class_id'];
            $check->execute([$class, $class, $class, $actor, $integers['quiz_id']]);
            $row = $check->fetch();
            if (!$row || ($row['role'] === 'teacher' && (!$class || !$row['assigned'] || (int) $row['teacher_owner'] !== $actor))) {
                throw new QuizProblem(403, 'Anda tidak memiliki akses untuk menerbitkan quest ini.');
            }
            if (($class && (!$row['active_class'] || ($row['audience'] !== 'all_classes' && !$row['quiz_class'])))
                || (!$class && $row['audience'] !== 'all_classes')
                || $integers['target'] > (int) $row['attempt_limit'] * ($kind === 'answers' ? (int) $row['question_count'] : 1)) {
                throw new QuizProblem(422, 'Kelas atau target tidak sesuai dengan kuis yang dipilih.');
            }
            $insert = $this->db->prepare('INSERT INTO eduquest.quests (title, quiz_id, starts_at, ends_at, target, xp, audience, created_by, kind) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
            $insert->execute([$title, $integers['quiz_id'], $times['starts_at']->format(DATE_ATOM), $times['ends_at']->format(DATE_ATOM), $integers['target'], $integers['xp'], $class ? 'selected_classes' : 'all_classes', $actor, $kind]);
            $id = (int) $insert->fetchColumn();
            if ($class) {
                $insertClass = $this->db->prepare('INSERT INTO eduquest.quest_classes (quest_id, class_id) VALUES (?, ?)');
                $insertClass->execute([$id, $class]);
            }
            $this->audit($actor, 'quest_create', $id);
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $error;
        }
    }

    public function archive(int $actor, int $questId): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT pg_advisory_xact_lock(476152004)');
            $update = $this->db->prepare(<<<'SQL'
                UPDATE eduquest.quests q SET archived_at = clock_timestamp()
                FROM eduquest.students s
                WHERE q.id = ? AND q.archived_at IS NULL AND s.id = ? AND s.enabled
                  AND (s.role = 'admin' OR (s.role = 'teacher' AND q.created_by = s.id
                    AND q.audience = 'selected_classes' AND EXISTS (
                      SELECT 1 FROM eduquest.quest_classes qc
                      JOIN eduquest.class_teachers ct ON ct.class_id = qc.class_id AND ct.teacher_id = s.id
                      JOIN eduquest.classes c ON c.id = qc.class_id AND c.archived_at IS NULL
                      WHERE qc.quest_id = q.id
                    ) AND NOT EXISTS (
                      SELECT 1 FROM eduquest.quest_classes qc
                      WHERE qc.quest_id = q.id AND NOT EXISTS (
                        SELECT 1 FROM eduquest.class_teachers ct
                        JOIN eduquest.classes c ON c.id = ct.class_id AND c.archived_at IS NULL
                        WHERE ct.class_id = qc.class_id AND ct.teacher_id = s.id
                      )
                    )))
                SQL);
            $update->execute([$questId, $actor]);
            if ($update->rowCount() !== 1) {
                throw new QuizProblem(404, 'Quest tidak ditemukan atau tidak dapat dihentikan.');
            }
            $this->audit($actor, 'quest_archive', $questId);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $error;
        }
    }

    private function audit(int $actor, string $action, int $questId): void
    {
        $statement = $this->db->prepare('INSERT INTO eduquest.admin_changes (actor_id, action, target) VALUES (?, ?, ?)');
        $statement->execute([$actor, $action, (string) $questId]);
    }

    public function recordAttempt(int $attemptId, string $kind = 'quiz_finish'): void
    {
        if (!in_array($kind, ['quiz_finish', 'review', 'answers'], true)) { throw new LogicException('Unknown quest activity.'); }
        if (!$this->db->inTransaction()) {
            throw new LogicException('Quest rewards must be recorded within the attempt transaction.');
        }
        $attempt = $this->db->prepare('SELECT student_id FROM eduquest.attempts WHERE id = ? AND finished_at IS NOT NULL');
        $attempt->execute([$attemptId]);
        $student = $attempt->fetchColumn();
        if ($student === false) {
            throw new LogicException('Quest activity requires a completed attempt.');
        }
        $lock = $this->db->prepare('SELECT pg_advisory_xact_lock(476152005, ?)');
        $lock->execute([$student]);
        $event = $this->db->prepare(<<<'SQL'
            INSERT INTO eduquest.quest_events (quest_id, student_id, attempt_id, amount)
            SELECT q.id, current_attempt.student_id, current_attempt.id,
                   CASE WHEN q.kind = 'answers' THEN answered.amount ELSE 1 END
            FROM eduquest.attempts current_attempt
            JOIN eduquest.quests q ON q.quiz_id = current_attempt.quiz_id
            JOIN eduquest.quizzes quiz ON quiz.id = q.quiz_id AND quiz.archived_at IS NULL
            CROSS JOIN LATERAL (
                SELECT count(*)::int AS amount FROM eduquest.answers
                WHERE attempt_id = current_attempt.id AND option_id IS NOT NULL
                  AND answer_updated_at >= q.starts_at AND answer_updated_at < q.ends_at
            ) answered
            WHERE current_attempt.id = ? AND q.archived_at IS NULL AND q.kind = ?
              AND (q.kind <> 'answers' OR answered.amount > 0)
              AND (quiz.audience = 'all_classes' OR EXISTS (
                SELECT 1 FROM eduquest.quiz_classes target
                JOIN eduquest.class_students member ON member.class_id = target.class_id
                JOIN eduquest.classes active ON active.id = member.class_id AND active.archived_at IS NULL
                WHERE target.quiz_id = quiz.id AND member.student_id = current_attempt.student_id
              ))
              AND (CASE WHEN q.kind = 'review' THEN clock_timestamp() ELSE current_attempt.finished_at END) >= q.starts_at
              AND (CASE WHEN q.kind = 'review' THEN clock_timestamp() ELSE current_attempt.finished_at END) < q.ends_at
              AND (q.audience = 'all_classes' OR EXISTS (
                SELECT 1 FROM eduquest.quest_classes qc
                JOIN eduquest.class_students cs ON cs.class_id = qc.class_id
                JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
                WHERE qc.quest_id = q.id AND cs.student_id = current_attempt.student_id
              ))
            ON CONFLICT DO NOTHING
            SQL);
        $event->execute([$attemptId, $kind]);
        $award = $this->db->prepare(<<<'SQL'
            INSERT INTO eduquest.quest_rewards (quest_id, student_id, xp)
            SELECT q.id, e.student_id, q.xp FROM eduquest.quest_events e
            JOIN eduquest.quests q ON q.id = e.quest_id
            WHERE e.attempt_id = ?
              AND (SELECT sum(counted.amount) FROM eduquest.quest_events counted
                   WHERE counted.quest_id = q.id AND counted.student_id = e.student_id) >= q.target
            ON CONFLICT (quest_id, student_id) DO NOTHING
            SQL);
        $award->execute([$attemptId]);
    }

    public function visible(int $student): array
    {
        $statement = $this->db->prepare(<<<'SQL'
            SELECT q.id, q.title, q.target, q.starts_at, q.ends_at, q.xp, q.kind, quiz.slug AS quiz_slug,
                   (SELECT COALESCE(sum(e.amount), 0)::int FROM eduquest.quest_events e
                    WHERE e.student_id = ? AND e.quest_id = q.id) AS progress,
                   (SELECT max(a.id) FROM eduquest.attempts a WHERE a.student_id = ?
                    AND a.quiz_id = q.quiz_id AND a.finished_at IS NOT NULL) AS result_id,
                   quiz.attempt_limit,
                   (SELECT count(*)::int FROM eduquest.attempts a WHERE a.student_id = ? AND a.quiz_id = q.quiz_id) AS attempts_used,
                   (SELECT count(*)::int FROM eduquest.attempts a WHERE a.student_id = ? AND a.quiz_id = q.quiz_id
                    AND a.finished_at IS NULL AND a.deadline > clock_timestamp()) AS attempts_active,
                   (SELECT count(*)::int FROM eduquest.attempts a WHERE a.student_id = ? AND a.quiz_id = q.quiz_id
                    AND a.finished_at IS NOT NULL AND NOT EXISTS (SELECT 1 FROM eduquest.quest_events e WHERE e.quest_id = q.id AND e.attempt_id = a.id AND e.student_id = a.student_id)) AS reviewable,
                   COALESCE((SELECT max(t.total) FROM (SELECT count(*)::int AS total FROM eduquest.questions question
                    JOIN eduquest.quiz_versions version ON version.id = question.version_id
                    WHERE version.quiz_id = q.quiz_id AND version.published_at IS NOT NULL GROUP BY version.id) t), 0) AS max_questions,
                   COALESCE(r.xp, 0) AS awarded_xp
            FROM eduquest.quests q
            JOIN eduquest.quizzes quiz ON quiz.id = q.quiz_id AND quiz.archived_at IS NULL
            LEFT JOIN eduquest.quest_rewards r ON r.quest_id = q.id AND r.student_id = ?
            WHERE q.archived_at IS NULL AND q.starts_at <= clock_timestamp() AND q.ends_at > clock_timestamp()
              AND EXISTS (SELECT 1 FROM eduquest.quiz_versions v WHERE v.quiz_id = quiz.id AND v.published_at IS NOT NULL)
              AND (quiz.audience = 'all_classes' OR EXISTS (
                SELECT 1 FROM eduquest.quiz_classes target
                JOIN eduquest.class_students member ON member.class_id = target.class_id
                JOIN eduquest.classes active ON active.id = member.class_id AND active.archived_at IS NULL
                WHERE target.quiz_id = quiz.id AND member.student_id = ?
              ))
              AND (q.audience = 'all_classes' OR EXISTS (
                SELECT 1 FROM eduquest.quest_classes qc
                JOIN eduquest.class_students cs ON cs.class_id = qc.class_id
                JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
                WHERE qc.quest_id = q.id AND cs.student_id = ?
              ))
            ORDER BY q.ends_at, q.id
            SQL);
        $statement->execute([$student, $student, $student, $student, $student, $student, $student, $student]);
        $rows = $statement->fetchAll();
        foreach ($rows as &$row) {
            $opportunities = max(0, (int) $row['attempt_limit'] - (int) $row['attempts_used']) + (int) $row['attempts_active'];
            $row['can_start'] = $opportunities > 0;
            $capacity = match ($row['kind']) {
                'answers' => $opportunities * (int) $row['max_questions'],
                'review' => $opportunities + (int) $row['reviewable'],
                default => $opportunities,
            };
            $row['target_reachable'] = (int) $row['awarded_xp'] > 0 || (int) $row['progress'] + $capacity >= (int) $row['target'];
        }
        unset($row);
        return $rows;
    }
}
