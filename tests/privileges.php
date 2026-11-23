<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';

$db = database();
$checks = $db->query(<<<'SQL'
    SELECT NOT (rolsuper OR rolcreatedb OR rolcreaterole OR rolreplication OR rolbypassrls) AS restricted_role,
           NOT has_schema_privilege(current_user, 'eduquest', 'CREATE') AS cannot_create_app_objects,
           NOT has_schema_privilege(current_user, 'eduquest_meta', 'USAGE') AS cannot_run_migrations,
           NOT has_column_privilege(current_user, 'eduquest.students', 'role', 'UPDATE') AS cannot_change_roles,
           NOT has_column_privilege(current_user, 'eduquest.students', 'password_hash', 'UPDATE') AS cannot_change_credentials,
           NOT has_column_privilege(current_user, 'eduquest.student_profiles', 'student_id', 'UPDATE') AS cannot_reassign_student_profiles,
           has_column_privilege(current_user, 'eduquest.student_profiles', 'school', 'UPDATE') AS can_save_academic_profile,
           NOT has_table_privilege(current_user, 'eduquest.options', 'INSERT,UPDATE,DELETE') AS cannot_change_keys,
           NOT has_table_privilege(current_user, 'eduquest.security_events', 'UPDATE,DELETE') AS cannot_erase_audit,
           NOT has_column_privilege(current_user, 'eduquest.community_questions', 'status', 'UPDATE') AS cannot_moderate_community,
           NOT has_column_privilege(current_user, 'eduquest.mentor_bookings', 'meeting_url', 'UPDATE') AS cannot_publish_meeting,
           has_column_privilege(current_user, 'eduquest.mentor_bookings', 'student_cancelled_at', 'UPDATE') AS can_mark_student_cancellation,
           has_column_privilege(current_user, 'eduquest.answers', 'option_id', 'UPDATE') AS can_save_answers
    FROM pg_roles WHERE rolname = current_user
    SQL)->fetch();

foreach ($checks as $name => $allowed) {
    if (!$allowed) {
        fwrite(STDERR, "FAIL: $name\n");
        exit(1);
    }
    echo "PASS: $name\n";
}
