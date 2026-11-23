<?php

declare(strict_types=1);

final class StudentProfile
{
    public const FIELDS = [
        'school' => ['Sekolah', 120],
        'class_name' => ['Kelas', 40],
        'program' => ['Jurusan', 80],
        'interests' => ['Minat belajar', 240],
        'bio' => ['Bio', 600],
    ];

    public function __construct(private PDO $db)
    {
    }

    public function details(int $student): array
    {
        $query = $this->db->prepare('SELECT school, class_name, program, interests, bio FROM eduquest.student_profiles WHERE student_id = ?');
        $query->execute([$student]);
        return $query->fetch() ?: array_fill_keys(array_keys(self::FIELDS), '');
    }

    public function save(int $student, array $input): void
    {
        $details = [];
        foreach (self::FIELDS as $key => [$label, $limit]) {
            $value = $input[$key] ?? '';
            if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
                throw new QuizProblem(422, "Isian $label tidak valid.");
            }
            $value = trim($value);
            if (mb_strlen($value) > $limit) {
                throw new QuizProblem(422, "Isian $label maksimal $limit karakter.");
            }
            $details[] = $value;
        }
        $query = $this->db->prepare(<<<'SQL'
            INSERT INTO eduquest.student_profiles (student_id, school, class_name, program, interests, bio)
            SELECT id, ?, ?, ?, ?, ? FROM eduquest.students WHERE id = ? AND enabled AND role = 'student'
            ON CONFLICT (student_id) DO UPDATE
            SET school = excluded.school, class_name = excluded.class_name, program = excluded.program,
                interests = excluded.interests, bio = excluded.bio
            SQL);
        $query->execute([...$details, $student]);
        if ($query->rowCount() === 0) {
            throw new QuizProblem(403, 'Akun siswa sudah tidak aktif.');
        }
    }
}
