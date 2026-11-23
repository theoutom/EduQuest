GRANT USAGE ON SCHEMA eduquest TO __RUNTIME_ROLE__;
GRANT SELECT, INSERT, DELETE ON eduquest.topics, eduquest.quizzes,
    eduquest.questions, eduquest.options TO __RUNTIME_ROLE__;
GRANT SELECT, DELETE ON eduquest.quiz_versions TO __RUNTIME_ROLE__;
GRANT INSERT (quiz_id, version, duration_seconds, passing_score, tutor_enabled) ON eduquest.quiz_versions TO __RUNTIME_ROLE__;
GRANT UPDATE (name) ON eduquest.topics TO __RUNTIME_ROLE__;
GRANT UPDATE (title, summary, topic, level, attempt_limit, archived_at) ON eduquest.quizzes TO __RUNTIME_ROLE__;
GRANT UPDATE (duration_seconds, passing_score, tutor_enabled, published_at) ON eduquest.quiz_versions TO __RUNTIME_ROLE__;
GRANT UPDATE (stem, snippet, explanation) ON eduquest.questions TO __RUNTIME_ROLE__;
GRANT SELECT (id, username, name, role, enabled), DELETE ON eduquest.students TO __RUNTIME_ROLE__;
GRANT INSERT (username, name, password_hash) ON eduquest.students TO __RUNTIME_ROLE__;
GRANT UPDATE (name, enabled, password_hash) ON eduquest.students TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.attempts TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.admin_changes TO __RUNTIME_ROLE__;
GRANT INSERT (actor_id, action, target) ON eduquest.admin_changes TO __RUNTIME_ROLE__;
GRANT USAGE ON SEQUENCE eduquest.quizzes_id_seq, eduquest.quiz_versions_id_seq,
    eduquest.questions_id_seq, eduquest.options_id_seq, eduquest.students_id_seq, eduquest.admin_changes_id_seq
    TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.community_questions, eduquest.community_comments TO __RUNTIME_ROLE__;
GRANT UPDATE (status, moderation_note, published_at) ON eduquest.community_questions TO __RUNTIME_ROLE__;
GRANT UPDATE (hidden_at) ON eduquest.community_comments TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.mentors, eduquest.mentor_slots, eduquest.mentor_bookings TO __RUNTIME_ROLE__;
GRANT INSERT (name, category, bio, rate, verified) ON eduquest.mentors TO __RUNTIME_ROLE__;
GRANT UPDATE (name, category, bio, rate, verified, enabled) ON eduquest.mentors TO __RUNTIME_ROLE__;
GRANT INSERT (mentor_id, starts_at, ends_at), UPDATE (enabled) ON eduquest.mentor_slots TO __RUNTIME_ROLE__;
GRANT UPDATE (status, message, meeting_url) ON eduquest.mentor_bookings TO __RUNTIME_ROLE__;
GRANT USAGE ON SEQUENCE eduquest.mentors_id_seq, eduquest.mentor_slots_id_seq TO __RUNTIME_ROLE__;
