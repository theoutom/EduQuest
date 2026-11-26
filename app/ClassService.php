<?php

declare(strict_types=1);

final class ClassService
{
    public function __construct(private PDO $db, private ?int $actor = null)
    {
    }

    public function create(string $name, string $year): int
    {
        $name = trim($name);
        $year = trim($year);
        if ($name === '' || mb_strlen($name) > 80 || $year === '' || mb_strlen($year) > 30) {
            throw new QuizProblem(422, 'Nama kelas dan tahun ajaran wajib diisi.');
        }
        $id = 0;
        $this->membershipChange(function () use ($name, $year, &$id): void {
            $statement = $this->db->prepare('INSERT INTO eduquest.classes (name, academic_year) VALUES (?, ?) RETURNING id');
            $statement->execute([$name, $year]);
            $id = (int) $statement->fetchColumn();
            $this->audit('class_create', (string) $id);
        });
        return $id;
    }

    public function assignStudent(int $class, int $student): void
    {
        $this->membershipChange(function () use ($class, $student): void {
        $statement = $this->db->prepare(<<<'SQL'
            INSERT INTO eduquest.class_students (class_id, student_id)
            SELECT c.id, s.id FROM eduquest.classes c CROSS JOIN eduquest.students s
            WHERE c.id = ? AND c.archived_at IS NULL AND s.id = ? AND s.role = 'student' AND s.enabled
            ON CONFLICT DO NOTHING
            SQL);
        $statement->execute([$class, $student]);
        if ($statement->rowCount() === 0) {
            $check = $this->db->prepare('SELECT 1 FROM eduquest.class_students cs JOIN eduquest.classes c ON c.id = cs.class_id JOIN eduquest.students s ON s.id = cs.student_id WHERE cs.class_id = ? AND cs.student_id = ? AND c.archived_at IS NULL AND s.enabled AND s.role = ?');
            $check->execute([$class, $student, 'student']);
            if (!$check->fetchColumn()) {
                throw new QuizProblem(422, 'Kelas atau akun siswa tidak tersedia.');
            }
            return;
        }
        $this->audit('class_assign_student', "$class:$student");
        });
    }

    public function list(): array
    {
        return $this->db->query('SELECT id, name, academic_year, archived_at FROM eduquest.classes ORDER BY academic_year DESC, name')->fetchAll();
    }

    public function quizClasses(int $quiz): array
    {
        $statement = $this->db->prepare('SELECT class_id FROM eduquest.quiz_classes WHERE quiz_id = ?');
        $statement->execute([$quiz]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function examClasses(int $exam): array
    {
        $statement = $this->db->prepare('SELECT class_id FROM eduquest.exam_classes WHERE exam_id = ?');
        $statement->execute([$exam]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function setExamAudience(int $exam, string $audience, array $classIds): void
    {
        if (!in_array($audience, ['all_classes', 'selected_classes'], true)
            || ($audience === 'selected_classes' && !$classIds)
            || ($audience === 'all_classes' && $classIds)) {
            throw new QuizProblem(422, 'Pilih sasaran jadwal yang valid.');
        }
        $this->membershipChange(function () use ($exam, $audience, $classIds): void {
            $update = $this->db->prepare('UPDATE eduquest.exam_schedule SET audience = ? WHERE id = ?');
            $update->execute([$audience, $exam]);
            if ($update->rowCount() !== 1) {
                throw new QuizProblem(404, 'Jadwal tidak ditemukan.');
            }
            $delete = $this->db->prepare('DELETE FROM eduquest.exam_classes WHERE exam_id = ?');
            $delete->execute([$exam]);
            $insert = $this->db->prepare('INSERT INTO eduquest.exam_classes (exam_id, class_id) SELECT ?, id FROM eduquest.classes WHERE id = ? AND archived_at IS NULL');
            foreach (array_unique($classIds) as $class) {
                $insert->execute([$exam, $class]);
                if ($insert->rowCount() !== 1) {
                    throw new QuizProblem(422, 'Kelas tidak tersedia.');
                }
            }
            $this->audit('exam_audience', (string) $exam);
        });
    }

    public function details(int $id): array
    {
        $statement = $this->db->prepare('SELECT id, name, academic_year, archived_at FROM eduquest.classes WHERE id = ?');
        $statement->execute([$id]);
        $class = $statement->fetch();
        if (!$class) {
            throw new QuizProblem(404, 'Kelas tidak ditemukan.');
        }
        $statement = $this->db->prepare(<<<'SQL'
            SELECT s.id, s.name, s.username, (cs.student_id IS NOT NULL) AS assigned
            FROM eduquest.students s LEFT JOIN eduquest.class_students cs ON cs.student_id = s.id AND cs.class_id = ?
            WHERE s.role = 'student' AND s.enabled ORDER BY s.name, s.id
            SQL);
        $statement->execute([$id]);
        $students = $statement->fetchAll();
        $statement = $this->db->prepare(<<<'SQL'
            SELECT s.id, s.name, s.username, (ct.teacher_id IS NOT NULL) AS assigned
            FROM eduquest.students s LEFT JOIN eduquest.class_teachers ct ON ct.teacher_id = s.id AND ct.class_id = ?
            WHERE s.role = 'teacher' AND s.enabled ORDER BY s.name, s.id
            SQL);
        $statement->execute([$id]);
        return ['class' => $class, 'students' => $students, 'teachers' => $statement->fetchAll()];
    }

    public function assignTeacher(int $class, int $teacher): void
    {
        $this->membershipChange(function () use ($class, $teacher): void {
        $statement = $this->db->prepare(<<<'SQL'
            INSERT INTO eduquest.class_teachers (class_id, teacher_id)
            SELECT c.id, s.id FROM eduquest.classes c CROSS JOIN eduquest.students s
            WHERE c.id = ? AND c.archived_at IS NULL AND s.id = ? AND s.role = 'teacher' AND s.enabled
            ON CONFLICT DO NOTHING
            SQL);
        $statement->execute([$class, $teacher]);
        if ($statement->rowCount() === 0) {
            $check = $this->db->prepare('SELECT 1 FROM eduquest.class_teachers ct JOIN eduquest.classes c ON c.id = ct.class_id JOIN eduquest.students s ON s.id = ct.teacher_id WHERE ct.class_id = ? AND ct.teacher_id = ? AND c.archived_at IS NULL AND s.enabled AND s.role = ?');
            $check->execute([$class, $teacher, 'teacher']);
            if (!$check->fetchColumn()) {
                throw new QuizProblem(422, 'Kelas atau akun guru tidak tersedia.');
            }
            return;
        }
        $this->audit('class_assign_teacher', "$class:$teacher");
        });
    }

    public function removeTeacher(int $class, int $teacher): void
    {
        $this->membershipChange(function () use ($class, $teacher): void {
        $statement = $this->db->prepare('DELETE FROM eduquest.class_teachers WHERE class_id = ? AND teacher_id = ?');
        $statement->execute([$class, $teacher]);
        $this->audit('class_remove_teacher', "$class:$teacher");
        });
    }

    public function teacherClasses(int $teacher): array
    {
        $statement = $this->db->prepare(<<<'SQL'
            SELECT c.id, c.name, c.academic_year
            FROM eduquest.classes c JOIN eduquest.class_teachers t ON t.class_id = c.id
            WHERE t.teacher_id = ? AND c.archived_at IS NULL ORDER BY c.name, c.academic_year
            SQL);
        $statement->execute([$teacher]);
        return $statement->fetchAll();
    }

    public function teacherQuizzes(int $teacher): array
    {
        $statement = $this->db->prepare(<<<'SQL'
            SELECT DISTINCT q.id, q.title, q.slug FROM eduquest.quizzes q
            JOIN eduquest.quiz_classes qc ON qc.quiz_id = q.id
            JOIN eduquest.class_teachers ct ON ct.class_id = qc.class_id
            JOIN eduquest.classes c ON c.id = ct.class_id AND c.archived_at IS NULL
            JOIN eduquest.students teacher ON teacher.id = ct.teacher_id AND teacher.enabled AND teacher.role = 'teacher'
            WHERE q.teacher_owner = ? AND ct.teacher_id = ? AND q.audience = 'selected_classes'
              AND NOT EXISTS (
                SELECT 1 FROM eduquest.quiz_classes target
                WHERE target.quiz_id = q.id AND NOT EXISTS (
                    SELECT 1 FROM eduquest.class_teachers assigned
                    JOIN eduquest.classes active ON active.id = assigned.class_id AND active.archived_at IS NULL
                    WHERE assigned.class_id = target.class_id AND assigned.teacher_id = ct.teacher_id
                )
              )
            ORDER BY q.id DESC
            SQL);
        $statement->execute([$teacher, $teacher]);
        return $statement->fetchAll();
    }

    public function teacherCanEdit(int $teacher, int $quiz): bool
    {
        foreach ($this->teacherQuizzes($teacher) as $item) {
            if ((int) $item['id'] === $quiz) {
                return true;
            }
        }
        return false;
    }

    public function teacherResults(int $teacher, int $quiz): array
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $this->db->query('SELECT pg_advisory_xact_lock_shared(476152004)');
            if (!$this->teacherCanEdit($teacher, $quiz)) {
                if ($ownsTransaction) { $this->db->commit(); }
                return [];
            }
            $statement = $this->db->prepare(<<<'SQL'
            SELECT s.name, a.score, a.finished_at
            FROM eduquest.attempts a JOIN eduquest.students s ON s.id = a.student_id
            WHERE a.quiz_id = ? AND a.finished_at IS NOT NULL AND EXISTS (
                SELECT 1 FROM eduquest.quiz_classes qc
                JOIN eduquest.class_teachers ct ON ct.class_id = qc.class_id AND ct.teacher_id = ?
                JOIN eduquest.class_students cs ON cs.class_id = qc.class_id AND cs.student_id = s.id
                JOIN eduquest.classes c ON c.id = qc.class_id AND c.archived_at IS NULL
                WHERE qc.quiz_id = a.quiz_id
            )
            ORDER BY a.finished_at DESC, a.id DESC LIMIT 100
            SQL);
            $statement->execute([$quiz, $teacher]);
            $results = $statement->fetchAll();
            if ($ownsTransaction) { $this->db->commit(); }
            return $results;
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->db->inTransaction()) { $this->db->rollBack(); }
            throw $error;
        }
    }

    public function removeStudent(int $class, int $student): void
    {
        $this->membershipChange(function () use ($class, $student): void {
        $statement = $this->db->prepare('DELETE FROM eduquest.class_students WHERE class_id = ? AND student_id = ?');
        $statement->execute([$class, $student]);
        $this->audit('class_remove_student', "$class:$student");
        });
    }

    public function setArchived(int $class, bool $archived): void
    {
        $this->membershipChange(function () use ($class, $archived): void {
            $statement = $this->db->prepare('UPDATE eduquest.classes SET archived_at = CASE WHEN CAST(? AS boolean) THEN clock_timestamp() ELSE NULL END WHERE id = ?');
            $statement->execute([$archived ? 'true' : 'false', $class]);
            if ($statement->rowCount() !== 1) {
                throw new QuizProblem(404, 'Kelas tidak ditemukan.');
            }
            $this->audit($archived ? 'class_archive' : 'class_restore', (string) $class);
        });
    }

    private function membershipChange(callable $change): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT pg_advisory_xact_lock(476152004)');
            $change();
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    private function audit(string $action, string $target): void
    {
        if ($this->actor === null) {
            return;
        }
        $statement = $this->db->prepare('INSERT INTO eduquest.admin_changes (actor_id, action, target) VALUES (?, ?, ?)');
        $statement->execute([$this->actor, $action, $target]);
    }

    public function setQuizAudience(int $quiz, string $audience, array $classIds): void
    {
        if (!in_array($audience, ['all_classes', 'selected_classes'], true)
            || ($audience === 'selected_classes' && $classIds === [])
            || ($audience === 'all_classes' && $classIds !== [])) {
            throw new QuizProblem(422, 'Pilih sasaran kuis yang valid.');
        }
        $this->db->beginTransaction();
        try {
            $this->db->query('SELECT pg_advisory_xact_lock(476152004)');
            $lock = $this->db->prepare('SELECT pg_advisory_xact_lock(476152003, ?)');
            $lock->execute([$quiz]);
            $update = $this->db->prepare('UPDATE eduquest.quizzes SET audience = ? WHERE id = ?');
            $update->execute([$audience, $quiz]);
            if ($update->rowCount() !== 1) {
                throw new QuizProblem(404, 'Kuis tidak ditemukan.');
            }
            $delete = $this->db->prepare('DELETE FROM eduquest.quiz_classes WHERE quiz_id = ?');
            $delete->execute([$quiz]);
            $insert = $this->db->prepare('INSERT INTO eduquest.quiz_classes (quiz_id, class_id) SELECT ?, id FROM eduquest.classes WHERE id = ? AND archived_at IS NULL');
            foreach (array_unique($classIds) as $class) {
                $insert->execute([$quiz, $class]);
                if ($insert->rowCount() !== 1) {
                    throw new QuizProblem(422, 'Kelas tidak tersedia.');
                }
            }
            $this->audit('quiz_audience', (string) $quiz);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
