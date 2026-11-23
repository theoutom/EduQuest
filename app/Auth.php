<?php

declare(strict_types=1);

final class Auth
{
    private const DUMMY_HASH = '$argon2id$v=19$m=19456,t=2,p=1$eTNFMllrbkRaRU0wcEdJTw$3dMjD7DsRbXIK3PNs30cnAKhcQUoJSuL3ApDcKPUH7w';

    public function __construct(private PDO $db)
    {
    }

    public static function username(string $value): string
    {
        return strtolower(trim($value));
    }

    public static function validUsername(string $username): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9._-]{2,47}$/D', $username) === 1;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1]);
    }

    public function login(string $username, string $password, string $ip): ?array
    {
        $remaining = $this->hitLimit('ip:' . hash('sha256', $ip), 30);
        $bucket = 'account:' . hash('sha256', $username);
        $valid = self::validUsername($username);
        if ($remaining === 0 && $valid) {
            $remaining = $this->hitLimit($bucket, 5);
        }
        if ($remaining > 0) {
            header('Retry-After: ' . $remaining);
            throw new QuizProblem(429, 'Terlalu banyak percobaan masuk. Coba lagi nanti.');
        }
        $account = $valid
            ? $this->query('SELECT id, role, password_hash FROM eduquest.students WHERE username = ? AND enabled', [$username])->fetch()
            : false;
        $hash = $account['password_hash'] ?? self::DUMMY_HASH;
        if (strlen($password) > 256 || !password_verify($password, $hash) || !$account) {
            return null;
        }

        $this->db->beginTransaction();
        try {
            self::lockAccount($this->db, (int) $account['id']);
            $duration = $account['role'] === 'admin' ? 7200 : 28800;
            $token = bin2hex(random_bytes(32));
            $session = $this->query(<<<'SQL'
                INSERT INTO eduquest.sessions (token_hash, student_id, expires_at, issued_role)
                SELECT ?, id, clock_timestamp() + make_interval(secs => ?), role
                FROM eduquest.students WHERE id = ? AND enabled AND role = ? AND password_hash = ?
                RETURNING student_id
                SQL, [hash('sha256', $token), $duration, $account['id'], $account['role'], $hash])->fetchColumn();
            if ($session === false) {
                $this->db->rollBack();
                return null;
            }
            $this->query('DELETE FROM eduquest.login_limits WHERE bucket = ?', [$bucket]);
            if ($account['role'] === 'admin') {
                $this->query("INSERT INTO eduquest.security_events (account_id, event) VALUES (?, 'admin_login')", [$session]);
            }
            $this->db->commit();
            return ['token' => $token, 'duration' => $duration, 'role' => $account['role']];
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function current(string $token): ?array
    {
        return $this->query(<<<'SQL'
            UPDATE eduquest.sessions s SET last_seen_at = clock_timestamp()
            FROM eduquest.students u
            WHERE s.token_hash = ? AND u.id = s.student_id AND u.enabled AND u.role = s.issued_role
              AND s.expires_at > clock_timestamp()
              AND s.last_seen_at > clock_timestamp() - CASE
                  WHEN s.issued_role = 'admin' THEN interval '10 minutes' ELSE interval '30 minutes' END
            RETURNING u.id, u.name, u.role
            SQL, [hash('sha256', $token)])->fetch() ?: null;
    }

    public function logout(string $token): void
    {
        $this->query(<<<'SQL'
            WITH revoked AS (
                DELETE FROM eduquest.sessions WHERE token_hash = ? RETURNING student_id, issued_role
            )
            INSERT INTO eduquest.security_events (account_id, event)
            SELECT student_id, 'admin_logout' FROM revoked WHERE issued_role = 'admin'
            SQL, [hash('sha256', $token)]);
    }

    public static function lockAccount(PDO $db, int $id): void
    {
        $lock = $db->prepare('SELECT pg_advisory_xact_lock(477202601, ?)');
        $lock->execute([$id]);
    }

    private function hitLimit(string $bucket, int $maximum): int
    {
        $result = $this->query(<<<'SQL'
            INSERT INTO eduquest.login_limits AS b (bucket, attempts, window_end)
            VALUES (?, 1, statement_timestamp() + interval '15 minutes')
            ON CONFLICT (bucket) DO UPDATE SET
                attempts = CASE WHEN b.window_end <= statement_timestamp() THEN 1 ELSE LEAST(b.attempts + 1, 31) END,
                window_end = CASE WHEN b.window_end <= statement_timestamp()
                    THEN statement_timestamp() + interval '15 minutes' ELSE b.window_end END
            RETURNING attempts, GREATEST(1, ceil(extract(epoch FROM window_end - statement_timestamp())))::int AS remaining
            SQL, [$bucket])->fetch();
        return $result['attempts'] > $maximum ? $result['remaining'] : 0;
    }

    private function query(string $sql, array $parameters): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        return $statement;
    }
}
