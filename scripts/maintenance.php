<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';

try {
    $db = database();
    $db->beginTransaction();
    $sessions = $db->exec(<<<'SQL'
        DELETE FROM eduquest.sessions
        WHERE expires_at <= clock_timestamp()
           OR last_seen_at <= clock_timestamp() - CASE
               WHEN issued_role = 'admin' THEN interval '10 minutes' ELSE interval '30 minutes' END
        SQL);
    $limits = $db->exec('DELETE FROM eduquest.login_limits WHERE window_end <= clock_timestamp()');
    $db->commit();
    printf("Removed %d expired sessions and %d expired login counters.\n", $sessions, $limits);
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Maintenance failed. Check the database connection.\n");
    exit(1);
}
