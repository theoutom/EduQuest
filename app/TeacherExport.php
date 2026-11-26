<?php
declare(strict_types=1);

final class TeacherExport
{
    public function __construct(private PDO $db) {}

    public function document(int $teacher, int $quiz, string $kind): array
    {
        if (!in_array($kind, ['scores', 'questions'], true)) {
            throw new QuizProblem(404, 'Dokumen tidak ditemukan.');
        }
        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
            if (!(new ClassService($this->db))->teacherCanEdit($teacher, $quiz)) {
                throw new QuizProblem(404, 'Kuis tidak ditemukan.');
            }
            $metadata = $this->db->prepare(<<<'SQL'
                SELECT q.title, v.id AS version_id, v.version FROM eduquest.quizzes q
                JOIN LATERAL (SELECT id, version FROM eduquest.quiz_versions
                    WHERE quiz_id = q.id AND published_at IS NOT NULL ORDER BY version DESC LIMIT 1) v ON true
                WHERE q.id = ? AND q.archived_at IS NULL
                SQL);
            $metadata->execute([$quiz]);
            $item = $metadata->fetch();
            if (!$item) { throw new QuizProblem(404, 'Kuis terbit tidak ditemukan.'); }
            if ($kind === 'scores') {
                $query = $this->db->prepare(<<<'SQL'
                    SELECT s.name, s.username, string_agg(DISTINCT c.name, ', ' ORDER BY c.name) AS classes,
                           a.score, a.finished_at
                    FROM eduquest.attempts a JOIN eduquest.students s ON s.id = a.student_id
                    JOIN eduquest.quiz_classes qc ON qc.quiz_id = a.quiz_id
                    JOIN eduquest.class_teachers ct ON ct.class_id = qc.class_id AND ct.teacher_id = ?
                    JOIN eduquest.class_students cs ON cs.class_id = qc.class_id AND cs.student_id = s.id
                    JOIN eduquest.classes c ON c.id = qc.class_id AND c.archived_at IS NULL
                    WHERE a.quiz_id = ? AND a.finished_at IS NOT NULL
                    GROUP BY a.id, s.id ORDER BY a.finished_at DESC, a.id DESC LIMIT 5001
                    SQL);
                $query->execute([$teacher, $quiz]);
                $records = $query->fetchAll();
                $rows = array_map(static fn (array $row): array => [$row['name'], $row['username'], $row['classes'],
                    (int) $row['score'], (new DateTimeImmutable($row['finished_at']))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d/m/Y H:i') . ' WIB'], $records);
                $result = ['title' => 'Nilai kuis: ' . $item['title'], 'headers' => ['Nama siswa', 'Username', 'Kelas', 'Nilai', 'Selesai'], 'rows' => $rows];
            } else {
                $query = $this->db->prepare(<<<'SQL'
                    SELECT q.position, q.stem, q.snippet, q.explanation,
                           string_agg(chr(64 + o.position) || '. ' || o.label, E'\n' ORDER BY o.position) AS options,
                           max(o.label) FILTER (WHERE o.is_correct) AS answer
                    FROM eduquest.questions q JOIN eduquest.options o ON o.question_id = q.id
                    WHERE q.version_id = ? GROUP BY q.id ORDER BY q.position LIMIT 5001
                    SQL);
                $query->execute([$item['version_id']]);
                $records = $query->fetchAll();
                $rows = array_map(static fn (array $row): array => [(string) $row['position'], $row['stem'], $row['snippet'] ?? '', $row['options'], $row['answer'], $row['explanation']], $records);
                $result = ['title' => 'Soal dan kunci: ' . $item['title'] . ' (versi ' . $item['version'] . ')', 'headers' => ['No.', 'Soal', 'Kode', 'Pilihan', 'Kunci', 'Pembahasan'], 'rows' => $rows];
            }
            if (count($records) > 5000) { throw new QuizProblem(422, 'Dokumen terlalu besar untuk diekspor sekaligus.'); }
            $this->db->commit();
            return $result;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $error;
        }
    }
}
