<?php
declare(strict_types=1);

final class MinigameService
{
    public function __construct(private PDO $db) {}

    private function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    private function transaction(callable $operation, bool $exclusive = false): mixed
    {
        $this->db->beginTransaction();
        try {
            $this->db->query($exclusive ? 'SELECT pg_advisory_xact_lock(476152004)' : 'SELECT pg_advisory_xact_lock_shared(476152004)');
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $error;
        }
    }

    public function create(int $actor, array $data): int
    {
        $text = [];
        foreach (['title' => 120, 'instruction' => 2000, 'explanation' => 4000] as $field => $limit) {
            $value = $data[$field] ?? null;
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
                throw new QuizProblem(422, 'Isi permainan belum valid.');
            }
            $text[$field] = trim($value);
            if ($text[$field] === '' || mb_strlen($text[$field]) > $limit) { throw new QuizProblem(422, 'Periksa judul, instruksi dan pembahasan.'); }
        }
        $raw = $data['steps'] ?? null;
        if (!is_string($raw) || !mb_check_encoding($raw, 'UTF-8') || str_contains($raw, "\0")) { throw new QuizProblem(422, 'Langkah tidak valid.'); }
        $steps = array_map('trim', preg_split('/\R/u', $raw));
        if (count($steps) < 2 || count($steps) > 8 || count(array_unique($steps)) !== count($steps)) {
            throw new QuizProblem(422, 'Masukkan 2 sampai 8 langkah berbeda, satu per baris.');
        }
        foreach ($steps as $step) { if ($step === '' || mb_strlen($step) > 500) { throw new QuizProblem(422, 'Setiap langkah wajib diisi dan maksimal 500 karakter.'); } }
        $class = $data['class_id'] ?? null;
        $xp = $data['xp'] ?? null;
        if (!is_string($class) || !preg_match('/^(0|[1-9][0-9]{0,8})$/D', $class)
            || !is_string($xp) || !preg_match('/^(0|[1-9][0-9]{0,3})$/D', $xp) || (int) $xp > 1000) {
            throw new QuizProblem(422, 'Pilih kelas dan XP yang valid.');
        }
        return $this->transaction(function () use ($actor, $text, $steps, $class, $xp): int {
            $user = $this->query("SELECT role FROM eduquest.students WHERE id = ? AND enabled AND role IN ('teacher', 'admin')", [$actor])->fetch();
            if (!$user) { throw new QuizProblem(403, 'Anda tidak dapat membuat permainan.'); }
            if ($user['role'] === 'teacher' && (!(int) $class || !$this->query(<<<'SQL'
                SELECT 1 FROM eduquest.class_teachers ct JOIN eduquest.classes c ON c.id = ct.class_id AND c.archived_at IS NULL
                WHERE ct.teacher_id = ? AND ct.class_id = ?
                SQL, [$actor, $class])->fetchColumn())) { throw new QuizProblem(403, 'Kelas ini tidak ditugaskan kepada Anda.'); }
            if ((int) $class && !$this->query('SELECT 1 FROM eduquest.classes WHERE id = ? AND archived_at IS NULL', [$class])->fetchColumn()) {
                throw new QuizProblem(422, 'Kelas tidak tersedia.');
            }
            $id = (int) $this->query(<<<'SQL'
                INSERT INTO eduquest.minigames (title, instruction, explanation, xp, audience, created_by)
                VALUES (?, ?, ?, ?, ?, ?) RETURNING id
                SQL, [$text['title'], $text['instruction'], $text['explanation'], $xp, (int) $class ? 'selected_classes' : 'all_classes', $actor])->fetchColumn();
            if ((int) $class) { $this->query('INSERT INTO eduquest.minigame_classes (game_id, class_id) VALUES (?, ?)', [$id, $class]); }
            foreach ($steps as $index => $body) {
                $this->query('INSERT INTO eduquest.minigame_steps (game_id, token, position, body) VALUES (?, ?, ?, ?)', [$id, bin2hex(random_bytes(16)), $index + 1, $body]);
            }
            $this->audit($actor, 'minigame_create', $id);
            return $id;
        });
    }

    public function catalog(int $student): array
    {
        return $this->query(<<<'SQL'
            SELECT g.id, g.title, g.instruction, g.xp,
                   (SELECT max(a.id) FROM eduquest.minigame_attempts a WHERE a.game_id = g.id AND a.student_id = ?) AS result_id
            FROM eduquest.minigames g
            WHERE g.archived_at IS NULL AND (g.audience = 'all_classes' OR EXISTS (
                SELECT 1 FROM eduquest.minigame_classes gc JOIN eduquest.class_students cs ON cs.class_id = gc.class_id
                JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
                WHERE gc.game_id = g.id AND cs.student_id = ?
            )) ORDER BY g.id DESC
            SQL, [$student, $student])->fetchAll();
    }

    public function show(int $student, int $id): array
    {
        return $this->transaction(function () use ($student, $id): array {
            $game = $this->available($student, $id);
            $game['steps'] = $this->query('SELECT token, body FROM eduquest.minigame_steps WHERE game_id = ? ORDER BY random()', [$id])->fetchAll();
            return $game;
        });
    }

    private function available(int $student, int $id): array
    {
        $game = $this->query(<<<'SQL'
            SELECT g.id, g.title, g.instruction, g.xp FROM eduquest.minigames g
            JOIN eduquest.students s ON s.id = ? AND s.enabled AND s.role = 'student'
            WHERE g.id = ? AND g.archived_at IS NULL AND (g.audience = 'all_classes' OR EXISTS (
                SELECT 1 FROM eduquest.minigame_classes gc JOIN eduquest.class_students cs ON cs.class_id = gc.class_id
                JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
                WHERE gc.game_id = g.id AND cs.student_id = s.id
            ))
            SQL, [$student, $id])->fetch();
        return $game ?: throw new QuizProblem(404, 'Permainan tidak tersedia.');
    }

    public function submit(int $student, int $id, string $submission, array $order): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $submission)) { throw new QuizProblem(422, 'Sesi permainan tidak valid.'); }
        return $this->transaction(function () use ($student, $id, $submission, $order): array {
            $game = $this->available($student, $id);
            $this->query('SELECT pg_advisory_xact_lock(476152006, ?)', [$student]);
            $existing = $this->query('SELECT id, game_id FROM eduquest.minigame_attempts WHERE student_id = ? AND submission_token = ?', [$student, $submission])->fetch();
            if ($existing) {
                if ((int) $existing['game_id'] !== $id) { throw new QuizProblem(409, 'Sesi permainan sudah digunakan.'); }
                return $this->result($student, (int) $existing['id']);
            }
            $correctOrder = $this->query('SELECT token FROM eduquest.minigame_steps WHERE game_id = ? ORDER BY position', [$id])->fetchAll(PDO::FETCH_COLUMN);
            if (count($order) !== count($correctOrder)) {
                throw new QuizProblem(422, 'Pilih setiap langkah tepat satu kali.');
            }
            foreach ($order as $item) {
                if (!is_string($item) || !preg_match('/^[a-f0-9]{32}$/D', $item) || !in_array($item, $correctOrder, true)) {
                    throw new QuizProblem(422, 'Pilihan langkah tidak valid.');
                }
            }
            if (count(array_unique($order, SORT_STRING)) !== count($correctOrder)) {
                throw new QuizProblem(422, 'Pilih setiap langkah tepat satu kali.');
            }
            $correct = array_values($order) === $correctOrder;
            $earned = 0;
            if ($correct) {
                $reward = $this->query('INSERT INTO eduquest.minigame_rewards (game_id, student_id, xp) VALUES (?, ?, ?) ON CONFLICT DO NOTHING RETURNING xp', [$id, $student, $game['xp']])->fetchColumn();
                if ($reward !== false) { $earned = (int) $reward; }
            }
            $attempt = (int) $this->query(<<<'SQL'
                INSERT INTO eduquest.minigame_attempts (game_id, student_id, submission_token, correct, earned_xp)
                VALUES (?, ?, ?, ?, ?) RETURNING id
                SQL, [$id, $student, $submission, $correct ? 'true' : 'false', $earned])->fetchColumn();
            return $this->result($student, $attempt);
        });
    }

    public function result(int $student, int $attempt): array
    {
        $result = $this->query(<<<'SQL'
            SELECT a.id, a.game_id, a.correct, a.earned_xp, a.submitted_at, g.title, g.explanation
            FROM eduquest.minigame_attempts a JOIN eduquest.minigames g ON g.id = a.game_id
            WHERE a.id = ? AND a.student_id = ?
            SQL, [$attempt, $student])->fetch();
        if (!$result) { throw new QuizProblem(404, 'Hasil permainan tidak ditemukan.'); }
        $result['correct_steps'] = $this->query('SELECT body FROM eduquest.minigame_steps WHERE game_id = ? ORDER BY position', [$result['game_id']])->fetchAll(PDO::FETCH_COLUMN);
        return $result;
    }

    public function totalXp(int $student): int
    {
        return (int) $this->query('SELECT COALESCE(sum(xp), 0) FROM eduquest.minigame_rewards WHERE student_id = ?', [$student])->fetchColumn();
    }

    public function manage(int $actor, bool $teacher): array
    {
        return $this->query(<<<'SQL'
            SELECT g.id, g.title, g.xp, g.archived_at FROM eduquest.minigames g
            WHERE NOT CAST(? AS boolean) OR (g.created_by = ? AND EXISTS (
                SELECT 1 FROM eduquest.minigame_classes gc JOIN eduquest.class_teachers ct ON ct.class_id = gc.class_id
                JOIN eduquest.classes c ON c.id = gc.class_id AND c.archived_at IS NULL
                WHERE gc.game_id = g.id AND ct.teacher_id = ?
            )) ORDER BY g.id DESC LIMIT 100
            SQL, [$teacher ? 'true' : 'false', $actor, $actor])->fetchAll();
    }

    public function archive(int $actor, int $id): void
    {
        $this->transaction(function () use ($actor, $id): void {
            $changed = $this->query(<<<'SQL'
                UPDATE eduquest.minigames g SET archived_at = clock_timestamp() FROM eduquest.students s
                WHERE g.id = ? AND g.archived_at IS NULL AND s.id = ? AND s.enabled
                  AND (s.role = 'admin' OR (s.role = 'teacher' AND g.created_by = s.id AND g.audience = 'selected_classes'
                    AND EXISTS (SELECT 1 FROM eduquest.minigame_classes gc JOIN eduquest.class_teachers ct ON ct.class_id = gc.class_id
                      JOIN eduquest.classes c ON c.id = ct.class_id AND c.archived_at IS NULL WHERE gc.game_id = g.id AND ct.teacher_id = s.id)
                    AND NOT EXISTS (SELECT 1 FROM eduquest.minigame_classes gc WHERE gc.game_id = g.id AND NOT EXISTS (
                      SELECT 1 FROM eduquest.class_teachers ct JOIN eduquest.classes c ON c.id = ct.class_id AND c.archived_at IS NULL
                      WHERE ct.class_id = gc.class_id AND ct.teacher_id = s.id))))
                SQL, [$id, $actor]);
            if ($changed->rowCount() !== 1) { throw new QuizProblem(404, 'Permainan tidak ditemukan.'); }
            $this->audit($actor, 'minigame_archive', $id);
        }, true);
    }

    private function audit(int $actor, string $action, int $id): void
    {
        $this->query('INSERT INTO eduquest.admin_changes (actor_id, action, target) VALUES (?, ?, ?)', [$actor, $action, (string) $id]);
    }
}
