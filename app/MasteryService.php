<?php
declare(strict_types=1);

final class MasteryService
{
    public function __construct(private PDO $db) {}

    private function query(string $sql, array $params = []): PDOStatement
    {
        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query;
    }

    public function save(int $actor, array $data): int
    {
        $title = trim(is_string($data['title'] ?? null) ? $data['title'] : '');
        $description = trim(is_string($data['description'] ?? null) ? $data['description'] : '');
        $topic = is_string($data['topic'] ?? null) ? $data['topic'] : '';
        $ids = [];
        foreach (['node_id', 'class_id', 'quiz_id'] as $field) {
            $value = $data[$field] ?? '0';
            if (!is_string($value) || !preg_match('/^(0|[1-9][0-9]{0,8})$/D', $value)) {
                throw new QuizProblem(422, 'Pilihan kompetensi tidak valid.');
            }
            $ids[$field] = (int) $value;
        }
        $prerequisites = $data['prerequisites'] ?? [];
        if (!is_array($prerequisites) || count($prerequisites) > 20 || $title === '' || mb_strlen($title) > 120
            || $description === '' || mb_strlen($description) > 2000 || !preg_match('/^[a-z0-9-]{1,80}$/D', $topic)) {
            throw new QuizProblem(422, 'Isi judul, deskripsi, topik, dan prasyarat dengan benar.');
        }
        $prerequisites = array_values(array_unique(array_map(static function ($value): int {
            if (!is_string($value) || !preg_match('/^[1-9][0-9]{0,8}$/D', $value)) {
                throw new QuizProblem(422, 'Prasyarat tidak valid.');
            }
            return (int) $value;
        }, $prerequisites)));
        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
            if ($ids['quiz_id']) { $this->query('SELECT pg_advisory_xact_lock(476152003, ?)', [$ids['quiz_id']]); }
            $this->db->query('SELECT pg_advisory_xact_lock(476152006)');
            $role = $this->query("SELECT role FROM eduquest.students WHERE id = ? AND enabled AND role IN ('teacher', 'admin')", [$actor])->fetchColumn();
            if (!$role) { throw new QuizProblem(403, 'Akun tidak dapat mengelola kompetensi.'); }
            $class = $ids['class_id'];
            if ($role === 'teacher' && (!$class || !$this->query(<<<'SQL'
                SELECT 1 FROM eduquest.class_teachers ct JOIN eduquest.classes c ON c.id = ct.class_id
                WHERE ct.teacher_id = ? AND ct.class_id = ? AND c.archived_at IS NULL
                SQL, [$actor, $class])->fetchColumn())) {
                throw new QuizProblem(403, 'Kelas tidak ditugaskan kepada guru.');
            }
            if ($class && !$this->query('SELECT 1 FROM eduquest.classes WHERE id = ? AND archived_at IS NULL', [$class])->fetchColumn()) {
                throw new QuizProblem(422, 'Kelas tidak tersedia.');
            }
            if (!$this->query('SELECT 1 FROM eduquest.topics WHERE slug = ?', [$topic])->fetchColumn()) {
                throw new QuizProblem(422, 'Topik tidak tersedia.');
            }
            $quiz = null;
            if ($ids['quiz_id']) {
                $quiz = $this->query(<<<'SQL'
                    SELECT q.id, q.audience, q.teacher_owner, q.topic, v.id AS version_id, v.passing_score,
                           EXISTS (SELECT 1 FROM eduquest.quiz_classes qc WHERE qc.quiz_id = q.id AND qc.class_id = ?) AS class_access
                    FROM eduquest.quizzes q JOIN LATERAL (
                        SELECT id, passing_score FROM eduquest.quiz_versions WHERE quiz_id = q.id AND published_at IS NOT NULL
                        ORDER BY version DESC LIMIT 1
                    ) v ON true WHERE q.id = ? AND q.archived_at IS NULL
                    SQL, [$class, $ids['quiz_id']])->fetch();
                if (!$quiz || $quiz['topic'] !== $topic || ($role === 'teacher' && (int) $quiz['teacher_owner'] !== $actor)
                    || (!$class && $quiz['audience'] !== 'all_classes')
                    || ($class && $quiz['audience'] !== 'all_classes' && !$quiz['class_access'])) {
                    throw new QuizProblem(422, 'Kuis terbit tidak sesuai dengan topik atau kelas.');
                }
            }
            $node = $ids['node_id'];
            if ($node) {
                $existing = $this->query('SELECT class_id, created_by FROM eduquest.mastery_nodes WHERE id = ?', [$node])->fetch();
                if (!$existing || (int) ($existing['class_id'] ?? 0) !== $class || ($role === 'teacher' && (int) $existing['created_by'] !== $actor)) {
                    throw new QuizProblem(404, 'Kompetensi tidak ditemukan.');
                }
            } else {
                $node = (int) $this->query('INSERT INTO eduquest.mastery_nodes (class_id, created_by) VALUES (?, ?) RETURNING id', [$class ?: null, $actor])->fetchColumn();
            }
            foreach ($prerequisites as $prerequisite) {
                $scope = $this->query('SELECT class_id FROM eduquest.mastery_nodes WHERE id = ?', [$prerequisite])->fetch();
                if (!$scope || ($scope['class_id'] !== null && (int) $scope['class_id'] !== $class)) {
                    throw new QuizProblem(422, 'Prasyarat harus tersedia bagi kelas yang sama.');
                }
                $cycle = $this->query(<<<'SQL'
                    WITH RECURSIVE ancestors(id) AS (
                        SELECT CAST(? AS integer)
                        UNION
                        SELECT p.prerequisite_node_id FROM ancestors a
                        JOIN LATERAL (SELECT id FROM eduquest.mastery_rules WHERE node_id = a.id ORDER BY version DESC LIMIT 1) r ON true
                        JOIN eduquest.mastery_prerequisites p ON p.rule_id = r.id
                    ) SELECT 1 FROM ancestors WHERE id = ?
                    SQL, [$prerequisite, $node])->fetchColumn();
                if ($cycle) { throw new QuizProblem(422, 'Prasyarat tidak boleh membentuk siklus.'); }
            }
            $version = (int) $this->query('SELECT COALESCE(max(version), 0) + 1 FROM eduquest.mastery_rules WHERE node_id = ?', [$node])->fetchColumn();
            $rule = (int) $this->query(<<<'SQL'
                INSERT INTO eduquest.mastery_rules (node_id, version, title, description, topic, quiz_id, quiz_version_id, passing_score)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id
                SQL, [$node, $version, $title, $description, $topic, $quiz['id'] ?? null, $quiz['version_id'] ?? null, $quiz['passing_score'] ?? null])->fetchColumn();
            foreach ($prerequisites as $prerequisite) {
                $this->query('INSERT INTO eduquest.mastery_prerequisites (rule_id, prerequisite_node_id) VALUES (?, ?)', [$rule, $prerequisite]);
            }
            $this->query('INSERT INTO eduquest.admin_changes (actor_id, action, target) VALUES (?, ?, ?)', [$actor, 'mastery_save', (string) $node]);
            $this->db->commit();
            return $node;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $error;
        }
    }

    public function recordAttempt(int $attemptId): void
    {
        if (!$this->db->inTransaction()) { throw new LogicException('Mastery evidence requires a transaction.'); }
        $student = $this->query('SELECT student_id FROM eduquest.attempts WHERE id = ? AND finished_at IS NOT NULL', [$attemptId])->fetchColumn();
        if ($student === false) { throw new LogicException('Mastery requires a finished attempt.'); }
        $this->query('INSERT INTO eduquest.mastery_processed_attempts (attempt_id) VALUES (?) ON CONFLICT DO NOTHING', [$attemptId]);
        $this->synchronize((int) $student);
    }

    private function synchronize(int $student): void
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) { $this->db->beginTransaction(); }
        try {
            $this->db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
            $this->db->query('SELECT pg_advisory_xact_lock_shared(476152006)');
            $this->query('SELECT pg_advisory_xact_lock(476152007, ?)', [$student]);
            do {
                $inserted = $this->query(<<<'SQL'
                    INSERT INTO eduquest.mastery_awards (node_id, rule_id, student_id, attempt_id)
                    SELECT n.id, r.id, s.id, evidence.id
                    FROM eduquest.students s
                    JOIN eduquest.mastery_nodes n ON n.class_id IS NULL OR EXISTS (
                        SELECT 1 FROM eduquest.class_students cs JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
                        WHERE cs.class_id = n.class_id AND cs.student_id = s.id
                    )
                    JOIN LATERAL (SELECT * FROM eduquest.mastery_rules WHERE node_id = n.id ORDER BY version DESC LIMIT 1) r ON true
                    JOIN eduquest.quizzes q ON q.id = r.quiz_id AND q.archived_at IS NULL
                    JOIN LATERAL (
                        SELECT a.id FROM eduquest.attempts a
                        WHERE a.student_id = s.id AND a.version_id = r.quiz_version_id
                          AND a.finished_at IS NOT NULL AND a.score >= r.passing_score
                        ORDER BY a.finished_at, a.id LIMIT 1
                    ) evidence ON true
                    WHERE s.id = ? AND s.enabled AND s.role = 'student'
                      AND r.quiz_version_id = (
                        SELECT latest.id FROM eduquest.quiz_versions latest
                        WHERE latest.quiz_id = q.id AND latest.published_at IS NOT NULL
                        ORDER BY latest.version DESC LIMIT 1
                      )
                      AND (q.audience = 'all_classes' OR EXISTS (
                        SELECT 1 FROM eduquest.quiz_classes qc
                        JOIN eduquest.class_students cs ON cs.class_id = qc.class_id
                        JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
                        WHERE qc.quiz_id = q.id AND cs.student_id = s.id
                      ))
                      AND NOT EXISTS (
                        SELECT 1 FROM eduquest.mastery_prerequisites p WHERE p.rule_id = r.id AND NOT EXISTS (
                            SELECT 1 FROM eduquest.mastery_awards achieved WHERE achieved.node_id = p.prerequisite_node_id AND achieved.student_id = s.id
                        )
                      )
                    ON CONFLICT (node_id, student_id) DO NOTHING
                    SQL, [$student])->rowCount();
            } while ($inserted > 0);
            if ($ownsTransaction) { $this->db->commit(); }
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->db->inTransaction()) { $this->db->rollBack(); }
            throw $error;
        }
    }

    public function visible(int $student): array
    {
        $this->synchronize($student);
        $rows = $this->query(<<<'SQL'
            SELECT n.id, r.id AS rule_id, r.title, r.description, r.topic, t.name AS topic_name, r.quiz_version_id, r.passing_score,
                   (r.quiz_version_id IS NOT NULL AND r.quiz_version_id IS DISTINCT FROM (
                       SELECT id FROM eduquest.quiz_versions WHERE quiz_id = r.quiz_id AND published_at IS NOT NULL ORDER BY version DESC LIMIT 1
                   )) AS needs_revision,
                   q.slug AS quiz_slug, q.archived_at AS quiz_archived,
                   EXISTS (SELECT 1 FROM eduquest.mastery_awards aw WHERE aw.node_id = n.id AND aw.student_id = ?) AS mastered,
                   EXISTS (SELECT 1 FROM eduquest.attempts a WHERE a.student_id = ? AND a.version_id = r.quiz_version_id AND a.finished_at IS NULL AND a.deadline > clock_timestamp()) AS in_progress,
                   (q.audience = 'all_classes' OR EXISTS (
                       SELECT 1 FROM eduquest.quiz_classes qc JOIN eduquest.class_students cs ON cs.class_id = qc.class_id
                       JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
                       WHERE qc.quiz_id = q.id AND cs.student_id = ?
                   )) AS quiz_access
            FROM eduquest.mastery_nodes n
            JOIN LATERAL (SELECT * FROM eduquest.mastery_rules WHERE node_id = n.id ORDER BY version DESC LIMIT 1) r ON true
            JOIN eduquest.topics t ON t.slug = r.topic LEFT JOIN eduquest.quizzes q ON q.id = r.quiz_id
            WHERE n.class_id IS NULL OR EXISTS (
                SELECT 1 FROM eduquest.class_students cs JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
                WHERE cs.class_id = n.class_id AND cs.student_id = ?
            ) ORDER BY t.name, n.id
            SQL, [$student, $student, $student, $student])->fetchAll();
        $prereqs = $this->query(<<<'SQL'
            SELECT p.rule_id, p.prerequisite_node_id, r.title,
                   EXISTS (SELECT 1 FROM eduquest.mastery_awards aw WHERE aw.node_id = p.prerequisite_node_id AND aw.student_id = ?) AS mastered
            FROM eduquest.mastery_prerequisites p
            JOIN LATERAL (SELECT title FROM eduquest.mastery_rules WHERE node_id = p.prerequisite_node_id ORDER BY version DESC LIMIT 1) r ON true
            SQL, [$student])->fetchAll();
        $byRule = [];
        foreach ($prereqs as $prereq) { $byRule[$prereq['rule_id']][] = $prereq; }
        foreach ($rows as &$row) {
            $row['prerequisites'] = $byRule[$row['rule_id']] ?? [];
            $locked = false;
            foreach ($row['prerequisites'] as $prereq) { if (!$prereq['mastered']) { $locked = true; break; } }
            $row['status'] = $row['mastered'] ? 'mastered' : ($locked ? 'locked' :
                (!$row['quiz_version_id'] || $row['quiz_archived'] !== null || !$row['quiz_access'] || $row['needs_revision'] ? 'unavailable' :
                    ($row['in_progress'] ? 'in_progress' : 'available')));
            if (!$row['quiz_access'] || $row['quiz_archived'] !== null) { $row['quiz_slug'] = null; }
        }
        unset($row);
        return $rows;
    }

    public function manage(int $actor, bool $teacher): array
    {
        $rows = $this->query(<<<'SQL'
            SELECT n.id, n.class_id, n.created_by, r.id AS rule_id, r.version, r.title, r.description, r.topic, r.quiz_id,
                   (r.quiz_version_id IS NOT NULL AND r.quiz_version_id IS DISTINCT FROM (
                       SELECT id FROM eduquest.quiz_versions WHERE quiz_id = r.quiz_id AND published_at IS NOT NULL ORDER BY version DESC LIMIT 1
                   )) AS needs_revision
            FROM eduquest.mastery_nodes n
            JOIN LATERAL (SELECT * FROM eduquest.mastery_rules WHERE node_id = n.id ORDER BY version DESC LIMIT 1) r ON true
            WHERE NOT CAST(? AS boolean) OR (n.created_by = ? AND EXISTS (
                SELECT 1 FROM eduquest.class_teachers ct JOIN eduquest.classes c ON c.id = ct.class_id AND c.archived_at IS NULL
                WHERE ct.class_id = n.class_id AND ct.teacher_id = ?
            )) ORDER BY n.id
            SQL, [$teacher ? 'true' : 'false', $actor, $actor])->fetchAll();
        foreach ($rows as &$row) {
            $row['prerequisites'] = array_map('intval', $this->query('SELECT prerequisite_node_id FROM eduquest.mastery_prerequisites WHERE rule_id = ? ORDER BY prerequisite_node_id', [$row['rule_id']])->fetchAll(PDO::FETCH_COLUMN));
        }
        unset($row);
        return $rows;
    }

    public function prerequisiteOptions(int $actor): array
    {
        $role = $this->query("SELECT role FROM eduquest.students WHERE id = ? AND enabled AND role IN ('admin', 'teacher')", [$actor])->fetchColumn();
        if (!$role) { throw new QuizProblem(403, 'Akun tidak dapat mengelola kompetensi.'); }
        return $this->query(<<<'SQL'
            SELECT n.id, n.class_id, c.name AS class_name, r.title
            FROM eduquest.mastery_nodes n
            JOIN LATERAL (SELECT title FROM eduquest.mastery_rules WHERE node_id = n.id ORDER BY version DESC LIMIT 1) r ON true
            LEFT JOIN eduquest.classes c ON c.id = n.class_id
            WHERE n.class_id IS NULL OR (c.archived_at IS NULL AND (
                ? = 'admin' OR EXISTS (
                    SELECT 1 FROM eduquest.class_teachers ct WHERE ct.class_id = n.class_id AND ct.teacher_id = ?
                )
            ))
            ORDER BY r.title, n.id
            SQL, [$role, $actor])->fetchAll();
    }
}
