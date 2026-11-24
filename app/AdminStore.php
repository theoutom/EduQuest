<?php

declare(strict_types=1);

final class AdminStore
{
    public const LEVELS = ['dasar' => 'Dasar', 'menengah' => 'Menengah', 'lanjutan' => 'Lanjutan'];

    public function __construct(private PDO $db, private int $actor)
    {
    }

    public function topics(): array
    {
        return $this->query(<<<'SQL'
            SELECT t.slug, t.name, count(q.id)::int AS used,
                   (SELECT count(*)::int FROM eduquest.community_questions c WHERE c.topic = t.slug) AS contributions
            FROM eduquest.topics t LEFT JOIN eduquest.quizzes q ON q.topic = t.slug
            GROUP BY t.slug ORDER BY t.name
            SQL)->fetchAll();
    }

    public function changeTopic(string $action, array $input): void
    {
        $slug = $this->slug($input);
        $this->mutate($action . '_topic', $slug, function () use ($action, $input, $slug): void {
            $statement = match ($action) {
                'create' => $this->query('INSERT INTO eduquest.topics (slug, name) VALUES (?, ?)', [$slug, $this->text($input, 'name', 80)]),
                'update' => $this->query('UPDATE eduquest.topics SET name = ? WHERE slug = ?', [$this->text($input, 'name', 80), $slug]),
                'delete' => $this->confirmed($input, fn () => $this->query('DELETE FROM eduquest.topics WHERE slug = ?', [$slug])),
                default => throw new QuizProblem(422, 'Tindakan topik tidak dikenali.'),
            };
            if ($statement->rowCount() === 0) {
                throw new QuizProblem(404, 'Topik tidak ditemukan.');
            }
        });
    }

    public function quizzes(): array
    {
        return $this->query(<<<'SQL'
            SELECT q.id, q.slug, q.title, q.archived_at, t.name AS topic_name,
                   max(v.version) FILTER (WHERE v.published_at IS NOT NULL) AS published,
                   max(v.version) FILTER (WHERE v.published_at IS NULL) AS draft
            FROM eduquest.quizzes q JOIN eduquest.topics t ON t.slug = q.topic
            LEFT JOIN eduquest.quiz_versions v ON v.quiz_id = q.id
            GROUP BY q.id, t.name ORDER BY q.archived_at NULLS FIRST, q.id DESC
            SQL)->fetchAll();
    }

    public function dailySchedule(): array
    {
        $quizzes = $this->query(<<<'SQL'
            SELECT q.id, q.title, version.duration_seconds FROM eduquest.quizzes q
            JOIN LATERAL (
                SELECT duration_seconds FROM eduquest.quiz_versions WHERE quiz_id = q.id AND published_at IS NOT NULL
                ORDER BY version DESC LIMIT 1
            ) version ON true
            WHERE q.archived_at IS NULL AND version.duration_seconds <= 300
            ORDER BY q.title
            SQL)->fetchAll();
        $schedule = $this->query(<<<'SQL'
            SELECT c.challenge_date, q.title FROM eduquest.daily_challenges c JOIN eduquest.quizzes q ON q.id = c.quiz_id
            WHERE c.challenge_date >= (statement_timestamp() AT TIME ZONE 'Asia/Jakarta')::date ORDER BY c.challenge_date
            SQL)->fetchAll();
        return compact('quizzes', 'schedule');
    }

    public function changeChallenge(string $action, string $day, ?int $quiz): void
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Jakarta'));
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $day, $parts)
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
            || $day < $today->format('Y-m-d') || $day > $today->modify('+1 year')->format('Y-m-d')
            || !in_array($action, ['save', 'remove'], true)) {
            throw new QuizProblem(422, 'Pilih tindakan dan tanggal WIB dalam satu tahun ke depan.');
        }
        $this->mutate('daily_challenge_' . $action, $day, function () use ($action, $day, $quiz): void {
            if ($action === 'remove') {
                $this->query('DELETE FROM eduquest.daily_challenges WHERE challenge_date = ?', [$day]);
                return;
            }
            $saved = $this->query(<<<'SQL'
                INSERT INTO eduquest.daily_challenges(challenge_date, quiz_id)
                SELECT CAST(? AS date), q.id FROM eduquest.quizzes q JOIN LATERAL (
                    SELECT duration_seconds FROM eduquest.quiz_versions WHERE quiz_id = q.id AND published_at IS NOT NULL
                    ORDER BY version DESC LIMIT 1
                ) v ON true
                WHERE q.id = ? AND q.archived_at IS NULL AND v.duration_seconds <= 300
                ON CONFLICT (challenge_date) DO UPDATE SET quiz_id = excluded.quiz_id
                SQL, [$day, $quiz])->rowCount();
            if ($saved === 0) {
                throw new QuizProblem(422, 'Pilih kuis terbit dengan durasi maksimal lima menit.');
            }
        });
    }

    public function examSchedule(): array
    {
        return $this->query('SELECT * FROM eduquest.exam_schedule ORDER BY starts_at DESC, id DESC')->fetchAll();
    }

    public function exam(int $id): array
    {
        return $this->one('SELECT * FROM eduquest.exam_schedule WHERE id = ?', [$id], 'Jadwal ujian');
    }

    public function createExam(array $input): int
    {
        $fields = $this->examFields($input);
        return $this->mutate('create_exam', $fields[0], fn (): int => (int) $this->query(<<<'SQL'
            INSERT INTO eduquest.exam_schedule(title, location, note, starts_at, ends_at)
            VALUES (?, ?, ?, ?, ?) RETURNING id
            SQL, $fields)->fetchColumn());
    }

    public function changeExam(int $id, string $action, array $input): void
    {
        $this->mutate($action . '_exam', (string) $id, function () use ($id, $action, $input): void {
            $current = $this->one('SELECT published FROM eduquest.exam_schedule WHERE id = ? FOR UPDATE', [$id], 'Jadwal ujian');
            switch ($action) {
                case 'update':
                    $this->query(<<<'SQL'
                        UPDATE eduquest.exam_schedule SET title = ?, location = ?, note = ?, starts_at = ?, ends_at = ? WHERE id = ?
                        SQL, [...$this->examFields($input), $id]);
                    break;
                case 'publish':
                    if ($this->query('UPDATE eduquest.exam_schedule SET published = true WHERE id = ? AND ends_at > clock_timestamp()', [$id])->rowCount() === 0) {
                        throw new QuizProblem(409, 'Jadwal ujian sudah berakhir.');
                    }
                    break;
                case 'withdraw':
                    $this->query('UPDATE eduquest.exam_schedule SET published = false WHERE id = ?', [$id]);
                    break;
                case 'delete':
                    if ($current['published']) {
                        throw new QuizProblem(409, 'Tarik jadwal dari siswa sebelum menghapusnya.');
                    }
                    $this->confirmed($input, fn () => $this->query('DELETE FROM eduquest.exam_schedule WHERE id = ?', [$id]));
                    break;
                default:
                    throw new QuizProblem(422, 'Tindakan jadwal tidak dikenali.');
            }
        });
    }

    private function examFields(array $input): array
    {
        $timezone = new DateTimeZone('Asia/Jakarta');
        $times = [];
        foreach (['starts', 'ends'] as $key) {
            $raw = $this->field($input, $key);
            $date = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $raw)
                ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $raw, $timezone)
                : false;
            if (!$date || $date->format('Y-m-d\TH:i') !== $raw || $date->format('Y') === '0000') {
                throw new QuizProblem(422, 'Isi waktu mulai dan selesai yang valid dalam WIB.');
            }
            $times[] = $date;
        }
        if ($times[1] <= $times[0]) {
            throw new QuizProblem(422, 'Waktu selesai harus setelah waktu mulai.');
        }
        return [$this->text($input, 'title', 120), $this->text($input, 'location', 120, false),
            $this->text($input, 'note', 600, false), $times[0]->format(DATE_ATOM), $times[1]->format(DATE_ATOM)];
    }

    public function quiz(int $id): array
    {
        $quiz = $this->one('SELECT * FROM eduquest.quizzes WHERE id = ?', [$id], 'Kuis');
        $quiz['versions'] = $this->query('SELECT * FROM eduquest.quiz_versions WHERE quiz_id = ? ORDER BY version DESC', [$id])->fetchAll();
        $quiz['draft'] = null;
        foreach ($quiz['versions'] as $version) {
            if ($version['published_at'] === null) {
                $quiz['draft'] = $version;
                break;
            }
        }
        $selection = $quiz['draft'] ?? ($quiz['versions'][0] ?? null);
        $quiz['questions'] = $selection ? $this->questions((int) $selection['id']) : [];
        return $quiz;
    }

    public function createQuiz(array $input): int
    {
        $slug = $this->slug($input);
        return $this->mutate('create_quiz', $slug, function () use ($input, $slug): int {
            $id = (int) $this->query(<<<'SQL'
                INSERT INTO eduquest.quizzes (slug, title, summary, topic, level, attempt_limit)
                VALUES (?, ?, ?, ?, ?, ?) RETURNING id
                SQL, [$slug, ...$this->quizFields($input)])->fetchColumn();
            $this->query(<<<'SQL'
                INSERT INTO eduquest.quiz_versions (quiz_id, version, duration_seconds, passing_score, tutor_enabled)
                VALUES (?, 1, ?, ?, ?)
                SQL, [$id, ...$this->timeFields($input)]);
            return $id;
        });
    }

    public function changeQuiz(int $id, string $action, array $input): void
    {
        $actions = ['update', 'settings', 'draft', 'publish', 'archive', 'restore', 'delete', 'question-save', 'question-delete'];
        if (!in_array($action, $actions, true)) {
            throw new QuizProblem(422, 'Tindakan kuis tidak dikenali.');
        }
        $this->mutate($action . '_quiz', (string) $id, function () use ($id, $action, $input): void {
            $this->query('SELECT pg_advisory_xact_lock(476152003, ?)', [$id]);
            $quiz = $this->quiz($id);
            if ($action === 'update') {
                $this->query(<<<'SQL'
                    UPDATE eduquest.quizzes SET title = ?, summary = ?, topic = ?, level = ?, attempt_limit = ? WHERE id = ?
                    SQL, [...$this->quizFields($input), $id]);
                return;
            }
            if ($action === 'archive' || $action === 'restore') {
                if ($action === 'archive') {
                    $this->confirmed($input, fn () => $this->query('UPDATE eduquest.quizzes SET archived_at = clock_timestamp() WHERE id = ?', [$id]));
                } else {
                    $this->query('UPDATE eduquest.quizzes SET archived_at = NULL WHERE id = ?', [$id]);
                }
                return;
            }
            if ($action === 'draft') {
                $this->copyDraft($quiz);
                return;
            }
            if ($action === 'delete') {
                $this->confirmed($input, function () use ($quiz, $id): void {
                    if (array_filter($quiz['versions'], static fn (array $v): bool => $v['published_at'] !== null)) {
                        throw new QuizProblem(409, 'Kuis yang pernah terbit bisa diarsipkan.');
                    }
                    foreach ($quiz['questions'] as $question) {
                        $this->deleteQuestion((int) $question['id']);
                    }
                    $this->query('DELETE FROM eduquest.quiz_versions WHERE quiz_id = ?', [$id]);
                    $this->query('DELETE FROM eduquest.quizzes WHERE id = ?', [$id]);
                });
                return;
            }
            $draft = $quiz['draft'] ?? throw new QuizProblem(409, 'Buat draf sebelum mengubah soal atau pengaturan.');
            $version = (int) $draft['id'];
            if ($this->field($input, 'draft_id') !== (string) $version) {
                throw new QuizProblem(409, 'Versi draf sudah berubah. Periksa draf terbaru sebelum menyimpan.');
            }
            if ($action === 'settings') {
                $this->query('UPDATE eduquest.quiz_versions SET duration_seconds = ?, passing_score = ?, tutor_enabled = ? WHERE id = ?', [...$this->timeFields($input), $version]);
            } elseif ($action === 'publish') {
                if ($quiz['archived_at'] !== null) {
                    throw new QuizProblem(409, 'Pulihkan kuis sebelum menerbitkan draf.');
                }
                $this->publish($version);
            } elseif ($action === 'question-delete') {
                $question = $this->number($input, 'question_id', 1);
                $this->one('SELECT id FROM eduquest.questions WHERE id = ? AND version_id = ?', [$question, $version], 'Soal draf');
                $this->confirmed($input, fn () => $this->deleteQuestion($question));
            } else {
                $this->saveQuestion($version, $input);
            }
        });
    }

    public function students(): array
    {
        return $this->query(<<<'SQL'
            SELECT id, username, name, enabled FROM eduquest.students
            WHERE role = 'student' ORDER BY name, id
            SQL)->fetchAll();
    }

    public function student(int $id): array
    {
        return $this->one(<<<'SQL'
            SELECT id, username, name, enabled,
                   (SELECT count(*) FROM eduquest.attempts WHERE student_id = u.id)::int AS attempts,
                   (SELECT count(*) FROM eduquest.mentor_bookings WHERE student_id = u.id)::int AS mentor_sessions
            FROM eduquest.students u WHERE role = 'student' AND id = ?
            SQL, [$id], 'Akun siswa');
    }

    public function createStudent(array $input): int
    {
        $username = Auth::username($this->field($input, 'username'));
        if (!Auth::validUsername($username)) {
            throw new QuizProblem(422, 'Nama pengguna harus berisi 3–48 huruf kecil, angka, titik, garis bawah, atau tanda hubung.');
        }
        return $this->mutate('create_student', $username, fn (): int => (int) $this->query(<<<'SQL'
            INSERT INTO eduquest.students (username, name, password_hash) VALUES (?, ?, ?) RETURNING id
            SQL, [$username, $this->text($input, 'name', 80), $this->password($input)])->fetchColumn());
    }

    public function changeStudent(int $id, string $action, array $input): void
    {
        if (!in_array($action, ['update', 'reset', 'disable', 'delete'], true)) {
            throw new QuizProblem(422, 'Tindakan akun tidak dikenali.');
        }
        $this->mutate($action . '_student', (string) $id, function () use ($id, $action, $input): void {
            Auth::lockAccount($this->db, $id);
            $student = $this->student($id);
            if ($action === 'update') {
                $this->query("UPDATE eduquest.students SET name = ? WHERE id = ? AND role = 'student'", [$this->text($input, 'name', 80), $id]);
                return;
            }
            if ($action === 'delete') {
                $this->confirmed($input, function () use ($student, $id): void {
                    if ($student['attempts'] > 0 || $student['mentor_sessions'] > 0) {
                        throw new QuizProblem(409, 'Akun yang memiliki riwayat kuis atau sesi mentor hanya bisa dinonaktifkan.');
                    }
                    $this->query("DELETE FROM eduquest.students WHERE id = ? AND role = 'student'", [$id]);
                });
            } elseif ($action === 'disable') {
                $this->confirmed($input, fn () => $this->query("UPDATE eduquest.students SET enabled = false, password_hash = NULL WHERE id = ? AND role = 'student'", [$id]));
            } else {
                $this->query("UPDATE eduquest.students SET enabled = true, password_hash = ? WHERE id = ? AND role = 'student'", [$this->password($input), $id]);
            }
        });
    }

    public function history(): array
    {
        return [
            'attempts' => $this->query(<<<'SQL'
                SELECT u.name, q.title, v.version, a.score, a.started_at, a.finished_at,
                       a.deadline <= statement_timestamp() AS expired
                FROM eduquest.attempts a JOIN eduquest.students u ON u.id = a.student_id
                JOIN eduquest.quizzes q ON q.id = a.quiz_id JOIN eduquest.quiz_versions v ON v.id = a.version_id
                ORDER BY a.id DESC LIMIT 100
                SQL)->fetchAll(),
            'changes' => $this->query(<<<'SQL'
                SELECT u.name, c.action, c.target, c.happened_at
                FROM eduquest.admin_changes c JOIN eduquest.students u ON u.id = c.actor_id
                ORDER BY c.id DESC LIMIT 50
                SQL)->fetchAll(),
        ];
    }

    private function questions(int $version): array
    {
        $rows = $this->query(<<<'SQL'
            SELECT q.id, q.stem, q.snippet, q.explanation, q.position,
                   COALESCE(json_agg(json_build_object('position', o.position, 'label', o.label, 'correct', o.is_correct)
                       ORDER BY o.position) FILTER (WHERE o.id IS NOT NULL), '[]') AS options
            FROM eduquest.questions q LEFT JOIN eduquest.options o ON o.question_id = q.id
            WHERE q.version_id = ? GROUP BY q.id ORDER BY q.position
            SQL, [$version])->fetchAll();
        foreach ($rows as &$question) {
            $question['options'] = json_decode($question['options'], true, flags: JSON_THROW_ON_ERROR);
        }
        return $rows;
    }

    private function copyDraft(array $quiz): void
    {
        if ($quiz['draft'] !== null) {
            return;
        }
        $latest = $quiz['versions'][0] ?? throw new QuizProblem(409, 'Versi kuis tidak tersedia.');
        $version = (int) $this->query(<<<'SQL'
            INSERT INTO eduquest.quiz_versions (quiz_id, version, duration_seconds, passing_score, tutor_enabled)
            VALUES (?, ?, ?, ?, ?) RETURNING id
            SQL, [$quiz['id'], $latest['version'] + 1, $latest['duration_seconds'], $latest['passing_score'], $latest['tutor_enabled'] ? 'true' : 'false'])->fetchColumn();
        $this->query(<<<'SQL'
            INSERT INTO eduquest.questions (version_id, position, stem, snippet, explanation)
            SELECT ?, position, stem, snippet, explanation FROM eduquest.questions WHERE version_id = ? ORDER BY position
            SQL, [$version, $latest['id']]);
        $this->query(<<<'SQL'
            INSERT INTO eduquest.options (question_id, position, label, is_correct)
            SELECT dest.id, o.position, o.label, o.is_correct
            FROM eduquest.options o JOIN eduquest.questions orig ON orig.id = o.question_id
            JOIN eduquest.questions dest ON dest.position = orig.position AND dest.version_id = ?
            WHERE orig.version_id = ?
            SQL, [$version, $latest['id']]);
    }

    private function publish(int $version): void
    {
        $this->query('SELECT id FROM eduquest.quiz_versions WHERE id = ? FOR UPDATE', [$version]);
        $questions = $this->questions($version);
        if ($questions === []) {
            throw new QuizProblem(422, 'Tambahkan setidaknya satu soal sebelum menerbitkan.');
        }
        foreach ($questions as $question) {
            if (count($question['options']) < 2 || count(array_filter($question['options'], static fn (array $o): bool => $o['correct'])) !== 1) {
                throw new QuizProblem(422, 'Setiap soal perlu dua pilihan atau lebih dan satu kunci jawaban.');
            }
        }
        $this->query('UPDATE eduquest.quiz_versions SET published_at = clock_timestamp() WHERE id = ?', [$version]);
    }

    private function saveQuestion(int $version, array $input): void
    {
        $stem = $this->text($input, 'stem', 3000);
        $snippet = $this->field($input, 'snippet');
        if (!mb_check_encoding($snippet, 'UTF-8') || str_contains($snippet, "\0") || mb_strlen($snippet) > 6000) {
            throw new QuizProblem(422, 'Potongan kode maksimal 6.000 karakter.');
        }
        $snippet = $snippet === '' ? null : $snippet;
        $explanation = $this->text($input, 'explanation', 4000);
        $correct = $this->field($input, 'correct');
        $options = [];
        foreach (['a', 'b', 'c', 'd', 'e'] as $index => $letter) {
            $label = $this->text($input, $letter, 400, false);
            if ($label !== '') {
                $options[$letter] = [$index + 1, $label, $letter === $correct ? 'true' : 'false'];
            }
        }
        if (!isset($options['a'], $options['b'], $options[$correct])) {
            throw new QuizProblem(422, 'Isi pilihan A dan B, lalu pilih satu kunci dari pilihan yang terisi.');
        }
        if ($this->field($input, 'question_id') !== '') {
            $id = $this->number($input, 'question_id', 1);
            $this->one('SELECT id FROM eduquest.questions WHERE id = ? AND version_id = ?', [$id, $version], 'Soal draf');
            $this->query('UPDATE eduquest.questions SET stem = ?, snippet = ?, explanation = ? WHERE id = ?', [$stem, $snippet, $explanation, $id]);
            $this->query('DELETE FROM eduquest.options WHERE question_id = ?', [$id]);
        } else {
            $id = (int) $this->query(<<<'SQL'
                INSERT INTO eduquest.questions (version_id, position, stem, snippet, explanation)
                SELECT ?, COALESCE(max(position), 0) + 1, ?, ?, ? FROM eduquest.questions WHERE version_id = ?
                RETURNING id
                SQL, [$version, $stem, $snippet, $explanation, $version])->fetchColumn();
        }
        foreach ($options as $option) {
            $this->query('INSERT INTO eduquest.options (question_id, position, label, is_correct) VALUES (?, ?, ?, ?)', [$id, ...$option]);
        }
    }

    private function deleteQuestion(int $id): void
    {
        $this->query('DELETE FROM eduquest.options WHERE question_id = ?', [$id]);
        $this->query('DELETE FROM eduquest.questions WHERE id = ?', [$id]);
    }

    private function quizFields(array $input): array
    {
        $level = $this->field($input, 'level');
        if (!isset(self::LEVELS[$level])) {
            throw new QuizProblem(422, 'Pilih tingkat kuis.');
        }
        return [$this->text($input, 'title', 140), $this->text($input, 'summary', 600), $this->text($input, 'topic', 60), $level, $this->number($input, 'attempt_limit', 1, 50)];
    }

    private function timeFields(array $input): array
    {
        return [$this->number($input, 'minutes', 1, 180) * 60, $this->number($input, 'passing', 0, 100),
            $this->field($input, 'tutor_enabled') === '1' ? 'true' : 'false'];
    }

    private function slug(array $input): string
    {
        $slug = strtolower(trim($this->field($input, 'slug')));
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) || strlen($slug) > 60) {
            throw new QuizProblem(422, 'Kode harus berisi huruf, angka atau tanda hubung, maksimal 60 karakter.');
        }
        return $slug;
    }

    private function password(array $input): string
    {
        $password = $this->field($input, 'password');
        if (!mb_check_encoding($password, 'UTF-8') || str_contains($password, "\0") || mb_strlen($password) < 15 || strlen($password) > 256) {
            throw new QuizProblem(422, 'Kata sandi perlu minimal 15 karakter dan maksimal 256 byte.');
        }
        return Auth::hashPassword($password);
    }

    private function number(array $input, string $key, int $min, int $max = 2147483647): int
    {
        $value = filter_var($this->field($input, $key), FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($value === false) {
            throw new QuizProblem(422, 'Nilai angka tidak valid.');
        }
        return $value;
    }

    private function text(array $input, string $key, int $limit, bool $required = true): string
    {
        $value = trim($this->field($input, $key));
        if (($required && $value === '') || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0") || mb_strlen($value) > $limit) {
            throw new QuizProblem(422, 'Periksa isian wajib dan batas panjang masing-masing kolom.');
        }
        return $value;
    }

    private function field(array $input, string $key): string
    {
        return is_string($input[$key] ?? null) ? $input[$key] : '';
    }

    private function confirmed(array $input, callable $operation): mixed
    {
        if ($this->field($input, 'confirm') !== '1') {
            throw new QuizProblem(422, 'Centang konfirmasi sebelum melanjutkan.');
        }
        return $operation();
    }

    private function one(string $sql, array $params, string $label): array
    {
        return $this->query($sql, $params)->fetch() ?: throw new QuizProblem(404, "$label tidak ditemukan.");
    }

    private function mutate(string $action, string $target, callable $operation): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $operation();
            $this->query('INSERT INTO eduquest.admin_changes (actor_id, action, target) VALUES (?, ?, ?)', [$this->actor, $action, $target]);
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof PDOException) {
                if ($error->getCode() === '23505') {
                    throw new QuizProblem(409, 'Kode atau nama pengguna sudah digunakan.');
                }
                if ($error->getCode() === '23503') {
                    throw new QuizProblem(409, 'Data terkait masih dipakai atau sudah berubah.');
                }
            }
            throw $error;
        }
    }

    private function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
}
