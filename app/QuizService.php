<?php

declare(strict_types=1);

final class QuizProblem extends RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}

final class QuizService
{
    public function __construct(private PDO $db)
    {
    }

    public function catalog(int $student, string $search = '', string $topic = '', string $level = '', bool $includeArchivedAttempts = false): array
    {
        $needle = strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']);
        return $this->query(<<<'SQL'
            SELECT q.id, q.slug, q.title, q.summary, q.topic, t.name AS topic_name, q.level, q.attempt_limit, q.archived_at,
                   v.id AS version_id, v.duration_seconds, v.passing_score, v.tutor_enabled,
                   (SELECT count(*) FROM eduquest.questions WHERE version_id = v.id)::int AS total,
                   history.used, history.active_id, history.last_result_id, history.has_finished
            FROM eduquest.quizzes q
            JOIN eduquest.topics t ON t.slug = q.topic
            JOIN LATERAL (
                SELECT * FROM eduquest.quiz_versions
                WHERE quiz_id = q.id AND published_at IS NOT NULL ORDER BY version DESC LIMIT 1
            ) v ON true
            CROSS JOIN LATERAL (
                SELECT count(*)::int AS used,
                       count(*) FILTER (WHERE finished_at IS NOT NULL) > 0 AS has_finished,
                       max(id) FILTER (WHERE finished_at IS NULL AND deadline > clock_timestamp()) AS active_id,
                       max(id) FILTER (WHERE finished_at IS NOT NULL OR deadline <= clock_timestamp()) AS last_result_id
                FROM eduquest.attempts WHERE student_id = ? AND quiz_id = q.id
            ) history
            WHERE (q.archived_at IS NULL OR (CAST(? AS boolean) AND history.used > 0))
              AND q.title ILIKE ? ESCAPE '!' AND q.topic = COALESCE(NULLIF(?, ''), q.topic)
              AND q.level = COALESCE(NULLIF(?, ''), q.level)
            ORDER BY q.id
            SQL, [$student, $includeArchivedAttempts ? 'true' : 'false', "%$needle%", $topic, $level])->fetchAll();
    }

    public function bookmarks(int $student): array
    {
        return $this->query('SELECT quiz_id FROM eduquest.quiz_bookmarks WHERE student_id = ?', [$student])->fetchAll(PDO::FETCH_COLUMN);
    }

    public function setBookmark(int $student, string $slug, bool $saved): void
    {
        if (!$saved) {
            $this->query(<<<'SQL'
                DELETE FROM eduquest.quiz_bookmarks b USING eduquest.quizzes q
                WHERE b.quiz_id = q.id AND b.student_id = ? AND q.slug = ?
                SQL, [$student, $slug]);
            return;
        }
        if (!$this->query(<<<'SQL'
            WITH available AS (
                SELECT q.id FROM eduquest.quizzes q
                WHERE q.slug = ? AND q.archived_at IS NULL
                  AND EXISTS (SELECT 1 FROM eduquest.quiz_versions v WHERE v.quiz_id = q.id AND v.published_at IS NOT NULL)
            ), saved AS (
                INSERT INTO eduquest.quiz_bookmarks (student_id, quiz_id) SELECT ?, id FROM available
                ON CONFLICT DO NOTHING
            )
            SELECT id FROM available
            SQL, [$slug, $student])->fetchColumn()) {
            throw new QuizProblem(404, 'Kuis tidak tersedia untuk disimpan.');
        }
    }

    public function topics(): array
    {
        return $this->query('SELECT slug, name FROM eduquest.topics ORDER BY name')->fetchAll();
    }

    public function overview(int $student, string $slug): array
    {
        $metadata = $this->findQuiz($student, $slug, true);
        $metadata['history'] = $this->query(<<<'SQL'
            SELECT id, score, finished_at, deadline <= clock_timestamp() AS expired
            FROM eduquest.attempts WHERE student_id = ? AND quiz_id = ? ORDER BY id DESC
            SQL, [$student, $metadata['id']])->fetchAll();
        return $metadata;
    }

    public function start(int $student, string $slug): array
    {
        $attempt = $this->transaction(function () use ($student, $slug): ?array {
            $this->query('SELECT pg_advisory_xact_lock(476152002, ?)', [$student]);
            $quiz = $this->findQuiz($student, $slug);
            $this->query('SELECT pg_advisory_xact_lock(476152003, ?)', [$quiz['id']]);
            $quiz = $this->findQuiz($student, $slug);
            $active = $this->query(<<<'SQL'
                SELECT id FROM eduquest.attempts WHERE student_id = ? AND quiz_id = ? AND finished_at IS NULL
                SQL, [$student, $quiz['id']])->fetchColumn();
            if ($active !== false) {
                $existing = $this->settle($this->lockedAttempt($student, (int) $active));
                if ($existing['score'] === null) {
                    return $existing;
                }
            }
            if ($quiz['used'] >= $quiz['attempt_limit']) {
                return null;
            }
            $invalid = $this->query(<<<'SQL'
                SELECT EXISTS (
                    SELECT q.id FROM eduquest.questions q LEFT JOIN eduquest.options o ON o.question_id = q.id
                    WHERE q.version_id = ? GROUP BY q.id
                    HAVING count(o.id) < 2 OR count(o.id) FILTER (WHERE o.is_correct) <> 1
                )
                SQL, [$quiz['version_id']])->fetchColumn();
            if ($quiz['total'] === 0 || $invalid) {
                throw new QuizProblem(409, 'Kuis belum siap dikerjakan.');
            }
            return $this->query(<<<'SQL'
                INSERT INTO eduquest.attempts (student_id, quiz_id, version_id, started_at, deadline)
                SELECT ?, ?, ?, t, t + make_interval(secs => ?) FROM (SELECT clock_timestamp() AS t) clock
                RETURNING id
                SQL, [$student, $quiz['id'], $quiz['version_id'], $quiz['duration_seconds']])->fetch();
        });
        return $attempt ?? throw new QuizProblem(409, 'Batas percobaan kuis sudah tercapai.');
    }

    public function attempt(int $student, int $id): array
    {
        return $this->transaction(function () use ($student, $id): array {
            $attempt = $this->settle($this->lockedAttempt($student, $id));
            if ($attempt['score'] !== null) {
                return $attempt;
            }
            $attempt['questions'] = $this->query(<<<'SQL'
                SELECT q.id, q.position, q.stem, q.snippet, a.option_id AS selected_id,
                       COALESCE(a.doubtful, false) AS doubtful,
                       json_agg(json_build_object('id', o.id, 'label', o.label) ORDER BY o.position) AS options
                FROM eduquest.questions q
                JOIN eduquest.options o ON o.question_id = q.id
                LEFT JOIN eduquest.answers a ON a.attempt_id = ? AND a.question_id = q.id
                WHERE q.version_id = ?
                GROUP BY q.id, a.option_id, a.doubtful ORDER BY q.position
                SQL, [$id, $attempt['version_id']])->fetchAll();
            foreach ($attempt['questions'] as &$question) {
                $question['options'] = json_decode($question['options'], true, flags: JSON_THROW_ON_ERROR);
            }
            return $attempt;
        });
    }

    public function save(int $student, int $id, int $question, ?int $option, bool $doubtful): void
    {
        $saved = $this->transaction(function () use ($student, $id, $question, $option, $doubtful): bool {
            $attempt = $this->settle($this->lockedAttempt($student, $id));
            if ($attempt['score'] !== null) {
                return false;
            }
            $valid = $this->query(<<<'SQL'
                SELECT q.id FROM eduquest.questions q
                LEFT JOIN eduquest.options o ON o.question_id = q.id AND o.id = ?
                WHERE q.id = ? AND q.version_id = ? AND (CAST(? AS integer) IS NULL OR o.id IS NOT NULL)
                SQL, [$option, $question, $attempt['version_id'], $option])->fetchColumn();
            if ($valid === false) {
                throw new QuizProblem(422, 'Pilihan jawaban tidak sesuai dengan soal.');
            }
            $this->db->exec('SAVEPOINT answer_write');
            $this->query(<<<'SQL'
                INSERT INTO eduquest.answers (attempt_id, version_id, question_id, option_id, doubtful)
                VALUES (?, ?, ?, ?, ?)
                ON CONFLICT (attempt_id, question_id) DO UPDATE
                SET option_id = EXCLUDED.option_id, doubtful = EXCLUDED.doubtful
                SQL, [$id, $attempt['version_id'], $question, $option, $doubtful ? 'true' : 'false']);
            $expired = $this->query('SELECT clock_timestamp() >= CAST(? AS timestamptz)', [$attempt['deadline']])->fetchColumn();
            if ($expired) {
                $this->db->exec('ROLLBACK TO SAVEPOINT answer_write');
                $this->settle($attempt);
                return false;
            }
            return true;
        });
        if (!$saved) {
            throw new QuizProblem(409, 'Sesi ini sudah berakhir. Buka hasil kuis untuk melihat pembahasan.');
        }
    }

    public function submit(int $student, int $id): array
    {
        return $this->transaction(function () use ($student, $id): array {
            $attempt = $this->settle($this->lockedAttempt($student, $id), true);
            return $this->summary($attempt);
        });
    }

    public function review(int $student, int $id): array
    {
        return $this->transaction(function () use ($student, $id): array {
            $attempt = $this->settle($this->lockedAttempt($student, $id));
            if ($attempt['score'] === null) {
                throw new QuizProblem(409, 'Pembahasan tersedia setelah kuis dikumpulkan.');
            }
            $questions = $this->query(<<<'SQL'
                WITH ordered_options AS (
                    SELECT o.*, row_number() OVER (PARTITION BY o.question_id ORDER BY o.position)::int AS ordinal
                    FROM eduquest.options o JOIN eduquest.questions q ON q.id = o.question_id
                    WHERE q.version_id = ?
                )
                SELECT q.id, row_number() OVER (ORDER BY q.position)::int AS position,
                       q.stem, q.snippet, q.explanation, chosen.label AS selected,
                       key.label AS correct, chr(64 + key.ordinal) AS correct_label,
                       chr(64 + chosen.ordinal) AS selected_label,
                       CASE WHEN chosen.id IS NULL THEN 'kosong' WHEN chosen.is_correct THEN 'benar' ELSE 'salah' END AS outcome
                FROM eduquest.questions q
                JOIN ordered_options key ON key.question_id = q.id AND key.is_correct
                LEFT JOIN eduquest.answers a ON a.attempt_id = ? AND a.question_id = q.id
                LEFT JOIN ordered_options chosen ON chosen.id = a.option_id
                WHERE q.version_id = ? ORDER BY q.position
                SQL, [$attempt['version_id'], $id, $attempt['version_id']])->fetchAll();
            return $this->summary($attempt) + $attempt + ['questions' => $questions];
        });
    }

    private function findQuiz(int $student, string $slug, bool $includeArchivedAttempts = false): array
    {
        foreach ($this->catalog($student, includeArchivedAttempts: $includeArchivedAttempts) as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
        }
        throw new QuizProblem(404, 'Kuis tidak ditemukan.');
    }

    public function history(int $student): array
    {
        return $this->query(<<<'SQL'
            SELECT a.id, q.title, a.score, a.finished_at,
                   a.finished_at IS NULL AND a.deadline > statement_timestamp() AS active
            FROM eduquest.attempts a JOIN eduquest.quizzes q ON q.id = a.quiz_id
            WHERE a.student_id = ? ORDER BY a.id DESC LIMIT 100
            SQL, [$student])->fetchAll();
    }

    public function activeSessions(int $student, int $featuredId): array
    {
        return $this->query(<<<'SQL'
            SELECT a.id, q.title
            FROM eduquest.attempts a JOIN eduquest.quizzes q ON q.id = a.quiz_id
            WHERE a.student_id = ? AND a.id <> ? AND a.finished_at IS NULL AND a.deadline > statement_timestamp()
            ORDER BY a.deadline, a.id
            SQL, [$student, $featuredId])->fetchAll();
    }

    public function progress(int $student): array
    {
        return $this->query(<<<'SQL'
            SELECT count(DISTINCT quiz_id)::int AS completed, round(avg(score))::int AS average,
                   floor(coalesce(sum(extract(epoch FROM (finished_at - started_at))), 0))::bigint AS seconds
            FROM eduquest.attempts
            WHERE student_id = ? AND finished_at IS NOT NULL
            SQL, [$student])->fetch();
    }

    public function weeklyActivity(int $student): array
    {
        return $this->query(<<<'SQL'
            WITH days AS (
                SELECT (statement_timestamp() AT TIME ZONE 'Asia/Jakarta')::date - n AS day
                FROM generate_series(0, 6) AS n
            )
            SELECT d.day, count(a.id)::int AS completed
            FROM days d
            LEFT JOIN eduquest.attempts a ON a.student_id = ?
                AND a.finished_at >= (d.day::timestamp AT TIME ZONE 'Asia/Jakarta')
                AND a.finished_at < ((d.day + 1)::timestamp AT TIME ZONE 'Asia/Jakarta')
            GROUP BY d.day ORDER BY d.day
            SQL, [$student])->fetchAll();
    }

    private function lockedAttempt(int $student, int $id): array
    {
        $attempt = $this->query(<<<'SQL'
            SELECT a.id, a.version_id, a.started_at, a.deadline, a.finished_at, a.score,
                   q.slug, q.title, q.topic, t.name AS topic_name, v.passing_score, v.tutor_enabled,
                   floor(extract(epoch FROM a.deadline) * 1000)::bigint AS deadline_ms
            FROM eduquest.attempts a
            JOIN eduquest.quizzes q ON q.id = a.quiz_id
            JOIN eduquest.topics t ON t.slug = q.topic
            JOIN eduquest.quiz_versions v ON v.id = a.version_id
            WHERE a.id = ? AND a.student_id = ? FOR UPDATE OF a
            SQL, [$id, $student])->fetch();
        if (!$attempt) {
            throw new QuizProblem(404, 'Percobaan kuis tidak ditemukan.');
        }
        return $attempt;
    }

    private function settle(array $attempt, bool $force = false): array
    {
        if ($attempt['score'] !== null) {
            return $attempt;
        }
        $clock = $this->query(<<<'SQL'
            SELECT t AS now, t >= CAST(? AS timestamptz) AS expired,
                   floor(extract(epoch FROM t) * 1000)::bigint AS now_ms FROM (SELECT clock_timestamp() AS t) clock
            SQL, [$attempt['deadline']])->fetch();
        $attempt['now_ms'] = $clock['now_ms'];
        if (!$force && !$clock['expired']) {
            return $attempt;
        }
        $totals = $this->counts($attempt);
        $score = (int) round($totals['correct'] * 100 / $totals['total']);
        $final = $this->query(<<<'SQL'
            UPDATE eduquest.attempts SET finished_at = LEAST(CAST(? AS timestamptz), deadline), score = ?
            WHERE id = ? RETURNING finished_at, score
            SQL, [$clock['now'], $score, $attempt['id']])->fetch();
        return array_replace($attempt, $final);
    }

    private function counts(array $attempt): array
    {
        return $this->query(<<<'SQL'
            SELECT count(*)::int AS total, (count(*) FILTER (WHERE o.is_correct))::int AS correct,
                   (count(*) FILTER (WHERE o.is_correct = false))::int AS wrong,
                   (count(*) FILTER (WHERE o.id IS NULL))::int AS blank
            FROM eduquest.questions q
            LEFT JOIN eduquest.answers a ON a.attempt_id = ? AND a.question_id = q.id
            LEFT JOIN eduquest.options o ON o.id = a.option_id
            WHERE q.version_id = ?
            SQL, [$attempt['id'], $attempt['version_id']])->fetch();
    }

    private function summary(array $attempt): array
    {
        $duration = $this->query(<<<'SQL'
            SELECT floor(extract(epoch FROM finished_at - started_at))::int FROM eduquest.attempts WHERE id = ?
            SQL, [$attempt['id']])->fetchColumn();
        return $this->counts($attempt) + [
            'score' => $attempt['score'],
            'passed' => $attempt['score'] >= $attempt['passing_score'],
            'duration' => $duration,
        ];
    }

    private function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    private function transaction(callable $operation): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
