<?php

declare(strict_types=1);

final class Mentor
{
    public const PAGE_SIZE = 25;
    public const CATEGORIES = ['pemrograman' => 'Pemrograman', 'basis-data' => 'Basis data', 'web' => 'Pengembangan web', 'karier' => 'Karier dan portofolio'];
    public const STATUSES = [
        'pending' => 'Menunggu konfirmasi', 'confirmed' => 'Terkonfirmasi', 'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan', 'expired' => 'Waktu pengajuan lewat', 'finished' => 'Sesi berakhir',
    ];

    public function __construct(private PDO $db)
    {
    }

    public function catalog(string $search, string $category, string $price, bool $available, bool $admin = false): array
    {
        $needle = strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']);
        return $this->query(<<<'SQL'
            SELECT m.*, upcoming.next_slot
            FROM eduquest.mentors m
            CROSS JOIN LATERAL (SELECT min(s.starts_at) AS next_slot FROM eduquest.mentor_slots s WHERE
                s.mentor_id = m.id AND s.enabled AND s.starts_at > statement_timestamp()
                AND NOT EXISTS (SELECT 1 FROM eduquest.mentor_bookings b WHERE b.slot_id = s.id
                    AND b.student_cancelled_at IS NULL AND b.status IN ('pending', 'confirmed'))) upcoming
            WHERE (m.enabled OR CAST(? AS boolean))
                AND (m.name ILIKE ? ESCAPE '!' OR m.bio ILIKE ? ESCAPE '!')
                AND (? = '' OR m.category = ?)
                AND (? = '' OR m.rate <= CAST(NULLIF(?, '') AS integer))
                AND (NOT CAST(? AS boolean) OR upcoming.next_slot IS NOT NULL)
            ORDER BY m.name, m.id
            SQL, [$admin ? 'true' : 'false', "%$needle%", "%$needle%", $category, $category, $price, $price,
                $available ? 'true' : 'false'])->fetchAll();
    }

    public function profile(int $id, bool $admin = false): array
    {
        return $this->one('SELECT * FROM eduquest.mentors WHERE id = ? AND (enabled OR CAST(? AS boolean))', [$id, $admin ? 'true' : 'false'], 'Mentor');
    }

    public function slots(int $mentor, int $number, bool $admin = false): array
    {
        return $this->query(<<<'SQL'
            SELECT s.*, EXISTS (SELECT 1 FROM eduquest.mentor_bookings b
                WHERE b.slot_id = s.id AND b.student_cancelled_at IS NULL AND b.status IN ('pending','confirmed')) AS booked
            FROM eduquest.mentor_slots s WHERE mentor_id = ? AND starts_at > statement_timestamp()
              AND (CAST(? AS boolean) OR (s.enabled AND NOT EXISTS (
                  SELECT 1 FROM eduquest.mentor_bookings b
                  WHERE b.slot_id = s.id AND b.student_cancelled_at IS NULL AND b.status IN ('pending','confirmed')
              )))
            ORDER BY starts_at, id LIMIT ? OFFSET ?
            SQL, [$mentor, $admin ? 'true' : 'false', self::PAGE_SIZE + 1, ($number - 1) * self::PAGE_SIZE])->fetchAll();
    }

    public function create(int $admin, array $input): int
    {
        return $this->transaction(function () use ($admin, $input): int {
            $id = (int) $this->query(<<<'SQL'
                INSERT INTO eduquest.mentors(name, category, bio, rate, verified) VALUES (?, ?, ?, ?, ?) RETURNING id
                SQL, $this->profileFields($input))->fetchColumn();
            $this->audit($admin, 'mentor_create', $id);
            return $id;
        });
    }

    public function manage(int $id, int $admin, string $action, array $input): void
    {
        $this->transaction(function () use ($id, $admin, $action, $input): void {
            $this->lockMentor($id);
            $this->profile($id, true);
            switch ($action) {
                case 'update':
                    $this->query('UPDATE eduquest.mentors SET name = ?, category = ?, bio = ?, rate = ?, verified = ? WHERE id = ?',
                        [...$this->profileFields($input), $id]);
                    break;
                case 'disable':
                case 'enable':
                    $this->query('UPDATE eduquest.mentors SET enabled = ? WHERE id = ?', [$action === 'enable' ? 'true' : 'false', $id]);
                    break;
                case 'add-slot':
                    $start = $this->input($input, 'starts');
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $start)) {
                        throw new QuizProblem(422, 'Masukkan waktu mulai dalam WIB.');
                    }
                    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $start, new DateTimeZone('Asia/Jakarta'));
                    $minutes = $this->number($input, 'minutes', 15, 120);
                    if (!$date || $date->format('Y-m-d\TH:i') !== $start || $date->getTimestamp() <= time()
                        || $date->getTimestamp() > time() + 365 * 86400 || $minutes % 15 !== 0) {
                        throw new QuizProblem(422, 'Pilih waktu mendatang dalam WIB dan durasi kelipatan 15 menit.');
                    }
                    $this->query('INSERT INTO eduquest.mentor_slots(mentor_id, starts_at, ends_at) VALUES (?, ?, ?)', [
                        $id, $date->format(DATE_ATOM), $date->modify("+$minutes minutes")->format(DATE_ATOM),
                    ]);
                    break;
                case 'close-slot':
                case 'open-slot':
                    $slot = $this->number($input, 'slot_id', 1);
                    $this->one('SELECT id FROM eduquest.mentor_slots WHERE id = ? AND mentor_id = ? AND starts_at > statement_timestamp()',
                        [$slot, $id], 'Slot');
                    if ($action === 'close-slot' && $this->query(<<<'SQL'
                        SELECT 1 FROM eduquest.mentor_bookings WHERE slot_id = ? AND status IN ('pending','confirmed')
                        AND student_cancelled_at IS NULL
                        SQL, [$slot])->fetchColumn()) {
                        throw new QuizProblem(409, 'Tangani pemesanan yang aktif sebelum menutup slot.');
                    }
                    $this->query('UPDATE eduquest.mentor_slots SET enabled = ? WHERE id = ?', [$action === 'open-slot' ? 'true' : 'false', $slot]);
                    break;
                default:
                    throw new QuizProblem(422, 'Tindakan mentor tidak dikenali.');
            }
            $this->audit($admin, 'mentor_' . $action, $id);
        });
    }

    public function book(int $mentor, int $student, array $input): void
    {
        $slot = $this->number($input, 'slot_id', 1);
        $note = $this->text($input, 'note', 600, false);
        $this->transaction(function () use ($mentor, $student, $slot, $note): void {
            Auth::lockAccount($this->db, $student);
            $this->lockMentor($mentor);
            $profile = $this->profile($mentor);
            $schedule = $this->one('SELECT * FROM eduquest.mentor_slots WHERE id = ? AND mentor_id = ?', [$slot, $mentor], 'Slot');
            if ($this->query(<<<'SQL'
                SELECT 1 FROM eduquest.mentor_bookings b JOIN eduquest.mentor_slots s ON s.id = b.slot_id
                WHERE b.student_id = ? AND b.status IN ('pending','confirmed') AND b.student_cancelled_at IS NULL
                  AND s.starts_at < CAST(? AS timestamptz) AND s.ends_at > CAST(? AS timestamptz)
                LIMIT 1
                SQL, [$student, $schedule['ends_at'], $schedule['starts_at']])->fetchColumn()) {
                throw new QuizProblem(409, 'Kamu sudah punya sesi lain pada waktu tersebut.');
            }
            if ($this->query(<<<'SQL'
                INSERT INTO eduquest.mentor_bookings(slot_id, student_id, note, rate)
                SELECT s.id, u.id, ?, ? FROM eduquest.mentor_slots s
                CROSS JOIN eduquest.students u
                WHERE s.id = ? AND s.enabled AND s.starts_at > clock_timestamp() AND u.id = ? AND u.enabled AND u.role = 'student'
                SQL, [$note, $profile['rate'], $slot, $student])->rowCount() === 0) {
                throw new QuizProblem(409, 'Slot sudah ditutup atau telah dimulai, atau akun sudah tidak aktif.');
            }
        });
    }

    public function nextSession(int $student): ?array
    {
        return $this->query(<<<'SQL'
            SELECT m.name AS mentor_name, s.starts_at
            FROM eduquest.mentor_bookings b
            JOIN eduquest.mentor_slots s ON s.id = b.slot_id
            JOIN eduquest.mentors m ON m.id = s.mentor_id
            WHERE b.student_id = ? AND b.status = 'confirmed' AND b.student_cancelled_at IS NULL
              AND s.starts_at > statement_timestamp()
            ORDER BY s.starts_at, b.id LIMIT 1
            SQL, [$student])->fetch() ?: null;
    }

    public function studentSessions(int $student, int $number): array
    {
        return $this->query(self::bookingSql(true) . <<<'SQL'
             WHERE b.student_id = ?
             ORDER BY (b.student_cancelled_at IS NULL AND
                 ((b.status = 'confirmed' AND s.ends_at > statement_timestamp()) OR
                  (b.status = 'pending' AND s.starts_at > statement_timestamp()))) DESC, b.id DESC
             LIMIT ? OFFSET ?
            SQL, [$student, self::PAGE_SIZE + 1, ($number - 1) * self::PAGE_SIZE])->fetchAll();
    }

    public function adminSessions(int $number): array
    {
        return $this->query(self::bookingSql() . <<<'SQL'
             ORDER BY CASE
                WHEN b.student_cancelled_at IS NULL AND b.status = 'pending' AND s.starts_at > statement_timestamp() THEN 0
                WHEN b.student_cancelled_at IS NULL AND b.status = 'confirmed' AND s.ends_at > statement_timestamp() THEN 1
                ELSE 2 END, b.id DESC
             LIMIT ? OFFSET ?
            SQL, [self::PAGE_SIZE + 1, ($number - 1) * self::PAGE_SIZE])->fetchAll();
    }

    public function session(int $id): array
    {
        return $this->one(self::bookingSql() . ' WHERE b.id = ?', [$id], 'Pemesanan');
    }

    public function cancel(int $student, array $input): void
    {
        $id = $this->number($input, 'booking_id', 1);
        if ($this->input($input, 'confirm') !== '1') {
            throw new QuizProblem(422, 'Centang konfirmasi pembatalan.');
        }
        $this->transaction(function () use ($student, $id): void {
            $known = $this->one(self::bookingSql() . ' WHERE b.id = ? AND b.student_id = ?', [$id, $student], 'Pemesanan');
            $this->lockMentor((int) $known['mentor_id']);
            if ($this->query(<<<'SQL'
                UPDATE eduquest.mentor_bookings b SET student_cancelled_at = clock_timestamp()
                FROM eduquest.mentor_slots s WHERE b.slot_id = s.id AND b.id = ? AND b.student_id = ?
                  AND s.starts_at > clock_timestamp() AND b.student_cancelled_at IS NULL AND b.status IN ('pending','confirmed')
                SQL, [$id, $student])->rowCount() === 0) {
                throw new QuizProblem(409, 'Pemesanan ini sudah berubah atau sesi sudah dimulai.');
            }
        });
    }

    public function decide(int $id, int $admin, string $action, array $input): void
    {
        $this->transaction(function () use ($id, $admin, $action, $input): void {
            $known = $this->session($id);
            $this->lockMentor((int) $known['mentor_id']);
            $session = $this->session($id);
            [$status, $url, $message] = match ([$action, $session['view_status']]) {
                ['confirm', 'pending'] => ['confirmed', $this->meetingUrl($input), null],
                ['reject', 'pending'] => ['rejected', null, $this->text($input, 'reason', 400)],
                ['cancel', 'confirmed'] => $session['can_cancel']
                    ? ['cancelled', null, $this->text($input, 'reason', 400)]
                    : throw new QuizProblem(409, 'Sesi sudah dimulai.'),
                default => throw new QuizProblem(409, 'Status pemesanan sudah berubah.'),
            };
            if ($this->query(<<<'SQL'
                UPDATE eduquest.mentor_bookings b SET status = ?, meeting_url = ?, message = ?
                FROM eduquest.mentor_slots s WHERE b.slot_id = s.id AND b.id = ? AND b.status = ?
                  AND b.student_cancelled_at IS NULL AND s.starts_at > clock_timestamp()
                SQL, [$status, $url, $message, $id, $session['view_status']])->rowCount() === 0) {
                throw new QuizProblem(409, 'Pemesanan ini sudah berubah atau sesi sudah dimulai.');
            }
            $this->audit($admin, 'mentor_booking_' . $action, $id);
        });
    }

    private static function bookingSql(bool $showLink = false): string
    {
        $meeting = $showLink ? <<<'SQL'
            , CASE WHEN b.status = 'confirmed' AND b.student_cancelled_at IS NULL AND s.ends_at > statement_timestamp()
                   THEN b.meeting_url END AS meeting_url
            SQL : '';
        return <<<SQL
            SELECT b.id, b.student_id, b.note, b.rate, b.message, s.mentor_id, s.starts_at, s.ends_at, m.name AS mentor_name,
                   u.name AS student_name,
                   b.student_cancelled_at IS NULL AND b.status IN ('pending','confirmed')
                       AND s.starts_at > statement_timestamp() AS can_cancel,
                   CASE WHEN b.student_cancelled_at IS NOT NULL THEN 'cancelled'
                        WHEN b.status = 'confirmed' AND s.ends_at <= statement_timestamp() THEN 'finished'
                        WHEN b.status = 'pending' AND s.starts_at <= statement_timestamp() THEN 'expired'
                        ELSE b.status END AS view_status$meeting
            FROM eduquest.mentor_bookings b JOIN eduquest.mentor_slots s ON s.id = b.slot_id
            JOIN eduquest.mentors m ON m.id = s.mentor_id JOIN eduquest.students u ON u.id = b.student_id
            SQL;
    }

    private function meetingUrl(array $input): string
    {
        $url = $this->text($input, 'url', 1000);
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $allowed = in_array($host, ['meet.google.com', 'meet.jit.si', 'zoom.us', 'teams.microsoft.com'], true)
            || str_ends_with($host, '.zoom.us');
        if (!filter_var($url, FILTER_VALIDATE_URL) || !$allowed || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || ($parts['port'] ?? 443) !== 443
            || !preg_match('~^/[^/?#]~', $parts['path'] ?? '')) {
            throw new QuizProblem(422, 'Gunakan URL sesi HTTPS resmi dari Google Meet, Zoom, Microsoft Teams, atau Jitsi Meet.');
        }
        return $url;
    }

    private function profileFields(array $input): array
    {
        $category = $this->input($input, 'category');
        if (!isset(self::CATEGORIES[$category])) {
            throw new QuizProblem(422, 'Pilih kategori mentor.');
        }
        return [$this->text($input, 'name', 100), $category, $this->text($input, 'bio', 1600),
            $this->number($input, 'rate', 0, 10000000), $this->input($input, 'verified') === '1' ? 'true' : 'false'];
    }

    private function input(array $source, string $key): string
    {
        return is_string($source[$key] ?? null) ? $source[$key] : '';
    }

    private function text(array $source, string $key, int $limit, bool $required = true): string
    {
        $value = trim($this->input($source, $key));
        if (($required && $value === '') || str_contains($value, "\0") || !mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $limit) {
            throw new QuizProblem(422, 'Periksa isian wajib dan panjang teks.');
        }
        return $value;
    }

    private function number(array $source, string $key, int $min, int $max = 2147483647): int
    {
        $value = filter_var($this->input($source, $key), FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($value === false) {
            throw new QuizProblem(422, 'Periksa isian angka.');
        }
        return $value;
    }

    private function lockMentor(int $id): void
    {
        $this->query('SELECT pg_advisory_xact_lock(476152005, ?)', [$id]);
    }

    private function audit(int $actor, string $action, int $target): void
    {
        $this->query('INSERT INTO eduquest.admin_changes(actor_id, action, target) VALUES (?, ?, ?)', [$actor, $action, (string) $target]);
    }

    private function one(string $sql, array $params, string $label): array
    {
        return $this->query($sql, $params)->fetch() ?: throw new QuizProblem(404, "$label tidak ditemukan.");
    }

    private function query(string $sql, array $params): PDOStatement
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
            $this->db->rollBack();
            if ($error instanceof PDOException && in_array($error->getCode(), ['23505', '23514'], true)) {
                throw new QuizProblem(409, 'Slot sudah dipesan atau bertabrakan dengan jadwal lain.');
            }
            throw $error;
        }
    }
}
