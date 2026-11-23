<?php

declare(strict_types=1);

final class Community
{
    public const LEVELS = ['dasar' => 'Dasar', 'menengah' => 'Menengah', 'lanjutan' => 'Lanjutan'];
    public const ORDERS = ['baru' => 'Terbaru', 'suara' => 'Suara terbanyak', 'diskusi' => 'Diskusi terbanyak'];
    public const STATES = ['pending' => 'Menunggu tinjauan', 'published' => 'Disetujui', 'rejected' => 'Perlu revisi', 'archived' => 'Diarsipkan'];
    private const LETTERS = ['a', 'b', 'c', 'd', 'e'];

    public function __construct(private PDO $db)
    {
    }

    public function topics(): array
    {
        return $this->query('SELECT slug, name FROM eduquest.topics ORDER BY name')->fetchAll();
    }

    public function tutorContext(int $id): array
    {
        return $this->query("SELECT topic, stem FROM eduquest.community_questions WHERE id = ? AND status = 'published'", [$id])->fetch()
            ?: throw new QuizProblem(404, 'Soal latihan tidak ditemukan.');
    }

    public function catalog(string $search, string $topic, string $level, string $order, int $page): array
    {
        $sort = match ($order) {
            'suara' => 'votes DESC, ',
            'diskusi' => 'comments DESC, ',
            default => '',
        };
        $search = strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']);
        $sql = <<<'SQL'
            SELECT q.id, q.title, q.stem, q.level, t.name AS topic_name, COALESCE(u.name, 'Anggota komunitas') AS author,
                   (SELECT count(*) FROM eduquest.community_votes WHERE question_id = q.id) AS votes,
                   (SELECT count(*) FROM eduquest.community_comments WHERE question_id = q.id AND hidden_at IS NULL) AS comments
            FROM eduquest.community_questions q JOIN eduquest.topics t ON t.slug = q.topic
            LEFT JOIN eduquest.students u ON u.id = q.author_id
            WHERE q.status = 'published' AND (q.title ILIKE :title ESCAPE '!' OR q.stem ILIKE :stem ESCAPE '!')
              AND q.topic = COALESCE(NULLIF(:topic, ''), q.topic) AND q.level = COALESCE(NULLIF(:level, ''), q.level)
            SQL;
        $items = $this->query($sql . " ORDER BY {$sort}q.published_at DESC, q.id DESC LIMIT 13 OFFSET :offset", [
            'title' => "%$search%", 'stem' => "%$search%", 'topic' => $topic, 'level' => $level, 'offset' => ($page - 1) * 12,
        ])->fetchAll();
        $trending = $this->query(<<<'SQL'
            SELECT t.slug, t.name, count(DISTINCT q.id) AS questions, count(v.student_id) AS votes
            FROM eduquest.community_questions q JOIN eduquest.topics t ON t.slug = q.topic
            LEFT JOIN eduquest.community_votes v ON v.question_id = q.id
            WHERE q.status = 'published'
            GROUP BY t.slug ORDER BY votes DESC, questions DESC, t.slug LIMIT 5
            SQL)->fetchAll();
        $contributors = $this->query(<<<'SQL'
            SELECT u.name, count(*) AS questions
            FROM eduquest.community_questions q JOIN eduquest.students u ON u.id = q.author_id
            WHERE q.status = 'published'
            GROUP BY u.id ORDER BY questions DESC, u.name, u.id LIMIT 5
            SQL)->fetchAll();
        return ['items' => array_slice($items, 0, 12), 'hasNext' => count($items) > 12, 'trending' => $trending, 'contributors' => $contributors];
    }

    public function submissions(int $actor, int $page): array
    {
        return $this->query(<<<'SQL'
            SELECT id, title, status, moderation_note FROM eduquest.community_questions
            WHERE author_id = ? ORDER BY id DESC LIMIT 21 OFFSET ?
            SQL, [$actor, ($page - 1) * 20])->fetchAll();
    }

    public function queue(string $status, int $page): array
    {
        return $this->query(<<<'SQL'
            SELECT q.id, q.title, q.status, COALESCE(u.name, 'Anggota komunitas') AS author
            FROM eduquest.community_questions q LEFT JOIN eduquest.students u ON u.id = q.author_id
            WHERE q.status = ? ORDER BY q.id DESC LIMIT 21 OFFSET ?
            SQL, [$status, ($page - 1) * 20])->fetchAll();
    }

    public function question(int $id, int $actor, bool $moderator = false, int $commentPage = 1): array
    {
        $item = $this->find($id, $actor, $moderator);
        $owns = $item['author_id'] === $actor;
        $item['owns'] = $owns;
        $item['options'] = [];
        foreach (self::LETTERS as $letter) {
            if ($item["option_$letter"] !== null) {
                $item['options'][$letter] = $item["option_$letter"];
            }
        }
        $comments = $this->query(<<<'SQL'
            SELECT c.id, c.body, c.created_at, c.hidden_at, c.author_id, COALESCE(u.name, 'Anggota komunitas') AS author
            FROM eduquest.community_comments c LEFT JOIN eduquest.students u ON u.id = c.author_id
            WHERE c.question_id = ? AND (CAST(? AS boolean) OR c.hidden_at IS NULL) ORDER BY c.id DESC LIMIT 21 OFFSET ?
            SQL, [$id, $moderator ? 'true' : 'false', ($commentPage - 1) * 20])->fetchAll();
        $item['comment_page'] = $commentPage;
        $item['more_comments'] = count($comments) > 20;
        $item['comments'] = array_slice($comments, 0, 20);
        if (!$moderator) {
            $item['selected'] = $this->query(
                'SELECT choice FROM eduquest.community_answers WHERE question_id = ? AND student_id = ?', [$id, $actor],
            )->fetchColumn() ?: null;
            $item['votes'] = $this->query('SELECT count(*) FROM eduquest.community_votes WHERE question_id = ?', [$id])->fetchColumn();
            $item['voted'] = $this->query('SELECT 1 FROM eduquest.community_votes WHERE question_id = ? AND student_id = ?', [$id, $actor])->fetchColumn() !== false;
            if (!$owns && $item['selected'] === null) {
                unset($item['correct'], $item['explanation']);
            }
            if (!$owns) {
                unset($item['moderation_note']);
            }
        }
        return $item;
    }

    public function submit(int $actor, array $input): int
    {
        return $this->transaction(fn (): int => (int) $this->query(<<<'SQL'
            INSERT INTO eduquest.community_questions (author_id, topic, level, title, stem, option_a, option_b,
                option_c, option_d, option_e, correct, explanation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id
            SQL, [$actor, ...$this->fields($input)])->fetchColumn());
    }

    public function interact(int $id, int $actor, string $action, array $input): void
    {
        $this->transaction(function () use ($id, $actor, $action, $input): void {
            $item = $this->find($id, $actor, false, true);
            if (in_array($action, ['update', 'delete'], true)) {
                if ($item['author_id'] !== $actor) {
                    throw new QuizProblem(403, 'Hanya kontributor yang dapat mengelola pengajuan ini.');
                }
                if ($action === 'update' && $item['status'] === 'pending') {
                    $this->query(<<<'SQL'
                        UPDATE eduquest.community_questions SET topic = ?, level = ?, title = ?, stem = ?, option_a = ?, option_b = ?,
                            option_c = ?, option_d = ?, option_e = ?, correct = ?, explanation = ?, revision = revision + 1
                        WHERE id = ? AND author_id = ?
                        SQL, [...$this->fields($input), $id, $actor]);
                    return;
                }
                if ($action === 'delete' && in_array($item['status'], ['pending', 'rejected'], true)) {
                    if ($this->input($input, 'confirm') !== '1') {
                        throw new QuizProblem(422, 'Centang konfirmasi sebelum menghapus pengajuan.');
                    }
                    $this->query('DELETE FROM eduquest.community_questions WHERE id = ? AND author_id = ?', [$id, $actor]);
                    return;
                }
                throw new QuizProblem(409, 'Pengajuan ini sudah ditinjau. Gunakan pengajuan baru untuk revisi.');
            }
            if ($item['status'] !== 'published') {
                throw new QuizProblem(409, 'Interaksi tersedia untuk soal yang terbit.');
            }
            match ($action) {
                'answer' => $this->saveAnswer($item, $actor, $this->input($input, 'choice')),
                'vote' => $this->query('INSERT INTO eduquest.community_votes VALUES (?, ?) ON CONFLICT DO NOTHING', [$id, $actor]),
                'unvote' => $this->query('DELETE FROM eduquest.community_votes WHERE question_id = ? AND student_id = ?', [$id, $actor]),
                'comment' => $this->query('INSERT INTO eduquest.community_comments (question_id, author_id, body) VALUES (?, ?, ?)', [$id, $actor, $this->text($input, 'body', 1000)]),
                'hide-comment' => $this->hideComment($id, $input, $actor),
                default => throw new QuizProblem(422, 'Tindakan komunitas tidak dikenali.'),
            };
        });
    }

    public function moderate(int $id, int $actor, string $action, array $input): void
    {
        $this->transaction(function () use ($id, $actor, $action, $input): void {
            $item = $this->find($id, $actor, true, true);
            if ($action === 'hide-comment') {
                $this->hideComment($id, $input);
            } else {
                if (in_array($action, ['publish', 'reject'], true) && $this->input($input, 'revision') !== (string) $item['revision']) {
                    throw new QuizProblem(409, 'Isi soal telah berubah. Buka kembali soal sebelum memutuskan.');
                }
                $transition = match ([$action, $item['status']]) {
                    ['publish', 'pending'] => ['published', null],
                    ['reject', 'pending'] => ['rejected', $this->text($input, 'reason', 400)],
                    ['archive', 'published'] => ['archived', $this->text($input, 'reason', 400)],
                    ['restore', 'archived'] => ['published', null],
                    default => throw new QuizProblem(409, 'Status soal sudah berubah. Muat ulang halaman.'),
                };
                $this->query(<<<'SQL'
                    UPDATE eduquest.community_questions SET status = ?, moderation_note = ?,
                        published_at = CASE WHEN ? = 'publish' THEN clock_timestamp() ELSE published_at END
                    WHERE id = ?
                    SQL, [...$transition, $action, $id]);
            }
            $this->query('INSERT INTO eduquest.admin_changes (actor_id, action, target) VALUES (?, ?, ?)', [$actor, 'community_' . $action, (string) $id]);
        });
    }

    private function find(int $id, int $actor, bool $moderator, bool $lock = false): array
    {
        $sql = <<<'SQL'
            SELECT q.*, t.name AS topic_name, COALESCE(u.name, 'Anggota komunitas') AS author
            FROM eduquest.community_questions q JOIN eduquest.topics t ON t.slug = q.topic
            LEFT JOIN eduquest.students u ON u.id = q.author_id
            WHERE q.id = ? AND (CAST(? AS boolean) OR q.status = 'published' OR q.author_id = ?)
            SQL;
        return $this->query($sql . ($lock ? ' FOR UPDATE OF q' : ''), [$id, $moderator ? 'true' : 'false', $actor])->fetch()
            ?: throw new QuizProblem(404, 'Soal komunitas tidak ditemukan.');
    }

    private function saveAnswer(array $item, int $actor, string $choice): void
    {
        if (!in_array($choice, self::LETTERS, true) || $item["option_$choice"] === null) {
            throw new QuizProblem(422, 'Pilih jawaban yang tersedia.');
        }
        $this->query('INSERT INTO eduquest.community_answers VALUES (?, ?, ?) ON CONFLICT DO NOTHING', [$item['id'], $actor, $choice]);
    }

    private function hideComment(int $id, array $input, ?int $author = null): void
    {
        $comment = filter_var($this->input($input, 'comment_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if (!$comment) {
            throw new QuizProblem(422, 'Komentar tidak valid.');
        }
        $sql = 'UPDATE eduquest.community_comments SET hidden_at = clock_timestamp() WHERE id = ? AND question_id = ? AND hidden_at IS NULL';
        if ($author !== null) {
            $sql .= ' AND author_id = ?';
        }
        if ($this->query($sql, $author === null ? [$comment, $id] : [$comment, $id, $author])->rowCount() === 0) {
            throw new QuizProblem(404, 'Komentar tidak ditemukan.');
        }
    }

    private function fields(array $input): array
    {
        $level = $this->input($input, 'level');
        if (!isset(self::LEVELS[$level])) {
            throw new QuizProblem(422, 'Pilih tingkat soal.');
        }
        $options = [];
        foreach (self::LETTERS as $letter) {
            $value = $this->text($input, $letter, 400, false);
            $options[$letter] = $value === '' ? null : $value;
        }
        $filled = array_keys(array_filter($options, static fn (?string $v): bool => $v !== null));
        $correct = $this->input($input, 'correct');
        if (count($filled) < 2 || $filled !== array_slice(self::LETTERS, 0, count($filled)) || !in_array($correct, $filled, true)) {
            throw new QuizProblem(422, 'Isi minimal dua pilihan secara berurutan mulai A dan pilih salah satunya sebagai kunci.');
        }
        return [
            $this->text($input, 'topic', 60), $level, $this->text($input, 'title', 140), $this->text($input, 'stem', 3000),
            ...array_values($options), $correct, $this->text($input, 'explanation', 4000),
        ];
    }

    private function text(array $source, string $key, int $max, bool $required = true): string
    {
        $value = trim($this->input($source, $key));
        if (($required && $value === '') || str_contains($value, "\0") || !mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $max) {
            throw new QuizProblem(422, 'Periksa kolom wajib dan panjang teks.');
        }
        return $value;
    }

    private function input(array $source, string $key): string
    {
        return is_string($source[$key] ?? null) ? $source[$key] : '';
    }

    private function transaction(callable $operation): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            $this->db->rollBack();
            if ($error instanceof PDOException && $error->getCode() === '23503') {
                throw new QuizProblem(409, 'Data terkait sudah berubah. Muat ulang halaman.');
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
