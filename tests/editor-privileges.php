<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';

$db = adminDatabase();
$checks = $db->query(<<<'SQL'
    SELECT NOT (rolsuper OR rolcreatedb OR rolcreaterole OR rolbypassrls) AS restricted_editor,
           NOT has_column_privilege(current_user, 'eduquest.students', 'role', 'UPDATE') AS cannot_grant_admin,
           NOT has_column_privilege(current_user, 'eduquest.students', 'password_hash', 'SELECT') AS cannot_read_password_hashes,
           NOT has_column_privilege(current_user, 'eduquest.attempts', 'score', 'UPDATE') AS cannot_rewrite_scores,
           NOT has_table_privilege(current_user, 'eduquest.admin_changes', 'DELETE') AS cannot_erase_editor_audit,
           NOT has_table_privilege(current_user, 'eduquest.sessions', 'DELETE') AS cannot_delete_sessions_directly,
           NOT has_table_privilege(current_user, 'eduquest.login_limits', 'DELETE') AS cannot_clear_login_limits_directly,
           has_column_privilege(current_user, 'eduquest.community_questions', 'status', 'UPDATE') AS can_moderate_community,
           NOT has_column_privilege(current_user, 'eduquest.community_questions', 'explanation', 'UPDATE') AS cannot_rewrite_contributions,
           has_column_privilege(current_user, 'eduquest.mentor_bookings', 'meeting_url', 'UPDATE') AS can_publish_meeting,
           NOT has_column_privilege(current_user, 'eduquest.mentor_bookings', 'student_id', 'INSERT') AS cannot_create_student_bookings,
           has_column_privilege(current_user, 'eduquest.questions', 'stem', 'UPDATE') AS can_edit_draft_content
    FROM pg_roles WHERE rolname = current_user
    SQL)->fetch();
$db->beginTransaction();
$checks['cannot_update_admin_accounts'] = $db->exec("UPDATE eduquest.students SET name = 'Fixture' WHERE role = 'admin'") === 0;
$db->rollBack();
foreach ($checks as $name => $passed) {
    if (!$passed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
