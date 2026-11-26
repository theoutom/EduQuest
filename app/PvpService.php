<?php
declare(strict_types=1);

final class PvpService
{
    public function __construct(private PDO $db) {}

    public function lobby(int $student): array
    {
        return $this->transaction(fn (): array => $this->readLobby($student));
    }

    private function readLobby(int $student): array
    {
        $classes = $this->query(<<<'SQL'
            SELECT c.id, c.name, c.academic_year FROM eduquest.classes c
            JOIN eduquest.class_students cs ON cs.class_id = c.id
            WHERE cs.student_id = ? AND c.archived_at IS NULL ORDER BY c.name
            SQL, [$student])->fetchAll();
        $peers = $this->query(<<<'SQL'
            SELECT DISTINCT s.id, s.name, c.name AS class_name, c.id AS class_id
            FROM eduquest.class_students mine JOIN eduquest.classes c ON c.id = mine.class_id AND c.archived_at IS NULL
            JOIN eduquest.class_students theirs ON theirs.class_id = c.id
            JOIN eduquest.students s ON s.id = theirs.student_id AND s.enabled AND s.role = 'student'
            WHERE mine.student_id = ? AND s.id <> ? ORDER BY c.name, s.name, s.id
            SQL, [$student, $student])->fetchAll();
        $quizzes = $this->query(<<<'SQL'
            SELECT DISTINCT q.id, q.title FROM eduquest.quizzes q
            WHERE q.archived_at IS NULL AND EXISTS (SELECT 1 FROM eduquest.quiz_versions v WHERE v.quiz_id = q.id AND v.published_at IS NOT NULL)
              AND (q.audience = 'all_classes' OR EXISTS (
                SELECT 1 FROM eduquest.quiz_classes qc JOIN eduquest.class_students cs ON cs.class_id = qc.class_id
                JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
                WHERE qc.quiz_id = q.id AND cs.student_id = ?
              )) ORDER BY q.title, q.id
            SQL, [$student])->fetchAll();
        $matches = $this->query(<<<'SQL'
            SELECT m.id, m.state, m.invitation_expires_at, m.deadline, q.title,
                   CASE WHEN m.challenger_id = ? THEN o.name ELSE c.name END AS opponent
            FROM eduquest.pvp_matches m JOIN eduquest.quizzes q ON q.id = m.quiz_id
            JOIN eduquest.students c ON c.id = m.challenger_id JOIN eduquest.students o ON o.id = m.opponent_id
            WHERE (m.challenger_id = ? OR m.opponent_id = ?)
              AND (m.state IN ('finished', 'cancelled') OR (
                q.archived_at IS NULL
                AND (q.audience = 'all_classes' OR EXISTS (
                  SELECT 1 FROM eduquest.quiz_classes qc WHERE qc.quiz_id = q.id AND qc.class_id = m.class_id
                ))
                AND EXISTS (SELECT 1 FROM eduquest.classes active WHERE active.id = m.class_id AND active.archived_at IS NULL)
                AND (SELECT count(*) FROM eduquest.class_students member JOIN eduquest.students person ON person.id = member.student_id
                     WHERE member.class_id = m.class_id AND person.id IN (m.challenger_id, m.opponent_id)
                       AND person.enabled AND person.role = 'student') = 2
              ))
            ORDER BY m.id DESC LIMIT 50
            SQL, [$student, $student, $student])->fetchAll();
        return compact('classes', 'peers', 'quizzes', 'matches');
    }

    public function invite(int $student, int $opponent, int $class, int $quiz): int
    {
        if ($student === $opponent || min($student, $opponent, $class, $quiz) < 1) {
            throw new QuizProblem(422, 'Pilih teman, kelas, dan kuis yang valid.');
        }
        return $this->transaction(function () use ($student, $opponent, $class, $quiz): int {
            $this->members($class, $student, $opponent);
            $this->query('SELECT pg_advisory_xact_lock(476152006, ?)', [$student]);
            $existing = $this->query(<<<'SQL'
                SELECT count(*)::int AS total,
                       count(*) FILTER (WHERE opponent_id = ? AND class_id = ? AND quiz_id = ?)::int AS same
                FROM eduquest.pvp_matches WHERE challenger_id = ? AND
                  ((state = 'pending' AND invitation_expires_at > clock_timestamp()) OR
                   (state = 'running' AND deadline > clock_timestamp()))
                SQL, [$opponent, $class, $quiz, $student])->fetch();
            if ((int) $existing['same'] > 0) throw new QuizProblem(409, 'Undangan atau pertandingan ini sudah ada.');
            if ((int) $existing['total'] >= 5) throw new QuizProblem(429, 'Terlalu banyak pertandingan aktif. Selesaikan dahulu pertandingan lain.');
            $version = $this->query(<<<'SQL'
                SELECT v.id, v.duration_seconds FROM eduquest.quizzes q
                JOIN LATERAL (SELECT * FROM eduquest.quiz_versions WHERE quiz_id = q.id AND published_at IS NOT NULL ORDER BY version DESC LIMIT 1) v ON true
                WHERE q.id = ? AND q.archived_at IS NULL AND (q.audience = 'all_classes' OR EXISTS (
                  SELECT 1 FROM eduquest.quiz_classes qc WHERE qc.quiz_id = q.id AND qc.class_id = ?
                ))
                SQL, [$quiz, $class])->fetch();
            if (!$version) throw new QuizProblem(404, 'Kuis tidak tersedia untuk kelas ini.');
            $valid = $this->query(<<<'SQL'
                SELECT count(*)::int AS total, count(*) FILTER (WHERE choice.total < 2 OR choice.correct <> 1)::int AS invalid
                FROM eduquest.questions q CROSS JOIN LATERAL (
                  SELECT count(*) AS total, count(*) FILTER (WHERE o.is_correct) AS correct
                  FROM eduquest.options o WHERE o.question_id = q.id
                ) choice WHERE q.version_id = ?
                SQL, [$version['id']])->fetch();
            if ((int) $valid['total'] === 0 || (int) $valid['invalid'] !== 0) throw new QuizProblem(409, 'Kuis belum siap untuk pertandingan.');
            return (int) $this->query(<<<'SQL'
                INSERT INTO eduquest.pvp_matches (class_id, quiz_id, version_id, challenger_id, opponent_id)
                VALUES (?, ?, ?, ?, ?) RETURNING id
                SQL, [$class, $quiz, $version['id'], $student, $opponent])->fetchColumn();
        });
    }

    public function accept(int $student, int $id): void
    {
        $accepted = $this->transaction(function () use ($student, $id): bool {
            $match = $this->locked($student, $id);
            if ((int) $match['opponent_id'] !== $student) throw new QuizProblem(403, 'Hanya penerima undangan yang dapat menerima.');
            if ($match['state'] === 'running') {
                $this->members((int) $match['class_id'], (int) $match['challenger_id'], (int) $match['opponent_id']);
                $this->quizAvailable($match);
                return true;
            }
            if ($match['state'] !== 'pending') return false;
            $this->members((int) $match['class_id'], (int) $match['challenger_id'], (int) $match['opponent_id']);
            $this->quizAvailable($match);
            $this->query(<<<'SQL'
                UPDATE eduquest.pvp_matches SET state = 'running', started_at = t.start_at,
                    deadline = t.start_at + make_interval(secs => (SELECT duration_seconds FROM eduquest.quiz_versions WHERE id = version_id))
                FROM (SELECT clock_timestamp() + interval '12 seconds' AS start_at) t
                WHERE id = ?
                SQL, [$id]);
            return true;
        });
        if (!$accepted) throw new QuizProblem(409, 'Undangan tidak lagi tersedia.');
    }

    public function answer(int $student, int $id, int $question, int $option): void
    {
        $saved = $this->transaction(function () use ($student, $id, $question, $option): bool {
            $match = $this->locked($student, $id);
            if ($match['state'] !== 'running') return false;
            $this->active($student, $match);
            $existing = $this->query('SELECT option_id FROM eduquest.pvp_answers WHERE match_id = ? AND student_id = ? AND question_id = ?', [$id, $student, $question])->fetchColumn();
            if ($existing !== false) {
                if ((int) $existing === $option) return true;
                throw new QuizProblem(409, 'Jawaban sudah dikirim.');
            }
            $this->db->exec('SAVEPOINT pvp_answer');
            $insert = $this->query(<<<'SQL'
                INSERT INTO eduquest.pvp_answers (match_id, student_id, question_id, option_id)
                SELECT ?, ?, q.id, o.id FROM eduquest.questions q JOIN eduquest.options o ON o.question_id = q.id
                WHERE q.id = ? AND o.id = ? AND q.version_id = ?
                ON CONFLICT DO NOTHING
                SQL, [$id, $student, $question, $option, $match['version_id']]);
            if (!$insert->rowCount()) {
                $valid = $this->query('SELECT 1 FROM eduquest.questions q JOIN eduquest.options o ON o.question_id = q.id WHERE q.id = ? AND o.id = ? AND q.version_id = ?', [$question, $option, $match['version_id']])->fetchColumn();
                throw new QuizProblem($valid ? 409 : 422, $valid ? 'Jawaban sudah dikirim.' : 'Pilihan tidak sesuai soal.');
            }
            if ($this->query('SELECT clock_timestamp() >= CAST(? AS timestamptz)', [$match['deadline']])->fetchColumn()) {
                $this->db->exec('ROLLBACK TO SAVEPOINT pvp_answer');
                $this->settle($id);
                return false;
            }
            return true;
        });
        if (!$saved) throw new QuizProblem(409, 'Waktu pertandingan sudah habis atau ronde telah selesai.');
    }

    public function cancel(int $student, int $id): void
    {
        $cancelled = $this->transaction(function () use ($student, $id): bool {
            $match = $this->locked($student, $id);
            if ($match['state'] !== 'pending') return false;
            $this->query("UPDATE eduquest.pvp_matches SET state = 'cancelled' WHERE id = ?", [$id]);
            return true;
        });
        if (!$cancelled) throw new QuizProblem(409, 'Undangan tidak lagi dapat dibatalkan.');
    }

    public function finish(int $student, int $id): void
    {
        $finished = $this->transaction(function () use ($student, $id): bool {
            $match = $this->locked($student, $id);
            $already = (int) $match['challenger_id'] === $student ? $match['challenger_finished_at'] : $match['opponent_finished_at'];
            if ($already !== null && $match['state'] === 'running') {
                $this->members((int) $match['class_id'], (int) $match['challenger_id'], (int) $match['opponent_id']);
                $this->quizAvailable($match);
                return true;
            }
            if ($already !== null && $match['state'] === 'finished') return true;
            if ($match['state'] !== 'running') return false;
            $this->active($student, $match);
            $column = $student === (int) $match['challenger_id'] ? 'challenger_finished_at' : 'opponent_finished_at';
            $this->query("UPDATE eduquest.pvp_matches SET $column = LEAST(clock_timestamp(), deadline) WHERE id = ? AND $column IS NULL", [$id]);
            $this->settle($id);
            return true;
        });
        if (!$finished) throw new QuizProblem(409, 'Ronde sudah selesai atau belum dimulai.');
    }

    public function view(int $student, int $id): array
    {
        return $this->transaction(function () use ($student, $id): array {
            $match = $this->locked($student, $id);
            if (in_array($match['state'], ['pending', 'running'], true)) {
                $this->members((int) $match['class_id'], (int) $match['challenger_id'], (int) $match['opponent_id']);
                $this->quizAvailable($match);
            }
            $match['questions'] = [];
            if ($match['state'] === 'running' && $match['round_ready']) {
                $match['questions'] = $this->query(<<<'SQL'
                    SELECT q.id, q.position, q.stem, q.snippet, a.option_id AS selected_id,
                           json_agg(json_build_object('id', o.id, 'label', o.label) ORDER BY o.position) AS options
                    FROM eduquest.questions q JOIN eduquest.options o ON o.question_id = q.id
                    LEFT JOIN eduquest.pvp_answers a ON a.question_id = q.id AND a.match_id = ? AND a.student_id = ?
                    WHERE q.version_id = ? GROUP BY q.id, a.option_id ORDER BY q.position
                    SQL, [$id, $student, $match['version_id']])->fetchAll();
                foreach ($match['questions'] as &$question) $question['options'] = json_decode($question['options'], true, flags: JSON_THROW_ON_ERROR);
                unset($question);
            }
            if ($match['state'] === 'finished') {
                $match['results'] = $this->query(<<<'SQL'
                    SELECT s.id, s.name, count(*) FILTER (WHERE o.is_correct)::int AS correct,
                           CASE WHEN s.id = m.challenger_id THEN m.challenger_finished_at ELSE m.opponent_finished_at END AS finished_at
                    FROM eduquest.pvp_matches m JOIN eduquest.students s ON s.id IN (m.challenger_id, m.opponent_id)
                    LEFT JOIN eduquest.pvp_answers a ON a.match_id = m.id AND a.student_id = s.id
                    LEFT JOIN eduquest.options o ON o.id = a.option_id
                    WHERE m.id = ? GROUP BY s.id, s.name, m.id ORDER BY correct DESC, finished_at ASC
                    SQL, [$id])->fetchAll();
                $match['review'] = $this->query(<<<'SQL'
                    SELECT q.position, q.stem, q.explanation, key.label AS correct, chosen.label AS selected
                    FROM eduquest.questions q JOIN eduquest.options key ON key.question_id = q.id AND key.is_correct
                    LEFT JOIN eduquest.pvp_answers a ON a.match_id = ? AND a.student_id = ? AND a.question_id = q.id
                    LEFT JOIN eduquest.options chosen ON chosen.id = a.option_id WHERE q.version_id = ? ORDER BY q.position
                    SQL, [$id, $student, $match['version_id']])->fetchAll();
            }
            return $match;
        });
    }

    private function active(int $student, array $match): void
    {
        $this->members((int) $match['class_id'], (int) $match['challenger_id'], (int) $match['opponent_id']);
        $this->quizAvailable($match);
        $finished = (int) $match['challenger_id'] === $student ? $match['challenger_finished_at'] : $match['opponent_finished_at'];
        if ($match['state'] !== 'running' || !$match['round_ready'] || $finished !== null) throw new QuizProblem(409, 'Ronde sudah selesai atau belum dimulai.');
    }

    private function members(int $class, int $student, int $opponent): void
    {
        $count = $this->query(<<<'SQL'
            SELECT count(DISTINCT s.id) FROM eduquest.class_students cs
            JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
            JOIN eduquest.students s ON s.id = cs.student_id AND s.enabled AND s.role = 'student'
            WHERE c.id = ? AND s.id IN (?, ?)
            SQL, [$class, $student, $opponent])->fetchColumn();
        if ((int) $count !== 2) throw new QuizProblem(404, 'Kelas atau peserta tidak tersedia.');
    }

    private function quizAvailable(array $match): void
    {
        $available = $this->query(<<<'SQL'
            SELECT 1 FROM eduquest.quizzes q JOIN eduquest.quiz_versions v ON v.id = ? AND v.quiz_id = q.id
            WHERE q.id = ? AND q.archived_at IS NULL AND v.published_at IS NOT NULL
              AND (q.audience = 'all_classes' OR EXISTS (
                SELECT 1 FROM eduquest.quiz_classes qc WHERE qc.quiz_id = q.id AND qc.class_id = ?
              ))
            SQL, [$match['version_id'], $match['quiz_id'], $match['class_id']])->fetchColumn();
        if (!$available) throw new QuizProblem(404, 'Kuis tidak lagi tersedia untuk kelas ini.');
    }

    private function settle(int $id): void
    {
        $this->query(<<<'SQL'
            UPDATE eduquest.pvp_matches SET
                state = CASE WHEN state = 'pending' AND invitation_expires_at <= clock_timestamp() THEN 'cancelled'
                             WHEN state = 'running' AND (deadline <= clock_timestamp() OR (challenger_finished_at IS NOT NULL AND opponent_finished_at IS NOT NULL)) THEN 'finished' ELSE state END,
                challenger_finished_at = CASE WHEN state = 'running' AND deadline <= clock_timestamp() THEN COALESCE(challenger_finished_at, deadline) ELSE challenger_finished_at END,
                opponent_finished_at = CASE WHEN state = 'running' AND deadline <= clock_timestamp() THEN COALESCE(opponent_finished_at, deadline) ELSE opponent_finished_at END
            WHERE id = ? AND state IN ('pending', 'running')
            SQL, [$id]);
    }

    private function locked(int $student, int $id): array
    {
        $match = $this->query(<<<'SQL'
            SELECT m.*, q.title, c.name AS challenger_name, o.name AS opponent_name,
                   clock_timestamp() >= m.started_at AS round_ready
            FROM eduquest.pvp_matches m JOIN eduquest.quizzes q ON q.id = m.quiz_id
            JOIN eduquest.students c ON c.id = m.challenger_id JOIN eduquest.students o ON o.id = m.opponent_id
            WHERE m.id = ? AND ? IN (m.challenger_id, m.opponent_id) FOR UPDATE OF m
            SQL, [$id, $student])->fetch();
        if (!$match) throw new QuizProblem(404, 'Pertandingan tidak ditemukan.');
        if (in_array($match['state'], ['pending', 'running'], true)) {
            $this->members((int) $match['class_id'], (int) $match['challenger_id'], (int) $match['opponent_id']);
            $this->quizAvailable($match);
        }
        $this->settle($id);
        return $this->query(<<<'SQL'
            SELECT m.*, q.title, c.name AS challenger_name, o.name AS opponent_name,
                   clock_timestamp() >= m.started_at AS round_ready
            FROM eduquest.pvp_matches m JOIN eduquest.quizzes q ON q.id = m.quiz_id
            JOIN eduquest.students c ON c.id = m.challenger_id JOIN eduquest.students o ON o.id = m.opponent_id
            WHERE m.id = ?
            SQL, [$id])->fetch();
    }

    private function query(string $sql, array $params = []): PDOStatement
    {
        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query;
    }

    private function transaction(callable $callback): mixed
    {
        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
            $value = $callback();
            $this->db->commit();
            return $value;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
