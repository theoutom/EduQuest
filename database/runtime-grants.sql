GRANT USAGE ON SCHEMA eduquest TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.students, eduquest.topics, eduquest.quizzes, eduquest.quiz_versions,
    eduquest.questions, eduquest.options TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.attempts, eduquest.answers TO __RUNTIME_ROLE__;
GRANT INSERT (student_id, quiz_id, version_id, started_at, deadline) ON eduquest.attempts TO __RUNTIME_ROLE__;
GRANT INSERT (attempt_id, version_id, question_id, option_id, doubtful) ON eduquest.answers TO __RUNTIME_ROLE__;
GRANT UPDATE (finished_at, score) ON eduquest.attempts TO __RUNTIME_ROLE__;
GRANT UPDATE (option_id, doubtful) ON eduquest.answers TO __RUNTIME_ROLE__;
GRANT SELECT, DELETE ON eduquest.sessions TO __RUNTIME_ROLE__;
GRANT INSERT (token_hash, student_id, expires_at, issued_role) ON eduquest.sessions TO __RUNTIME_ROLE__;
GRANT UPDATE (last_seen_at) ON eduquest.sessions TO __RUNTIME_ROLE__;
GRANT SELECT, DELETE ON eduquest.login_limits TO __RUNTIME_ROLE__;
GRANT INSERT (bucket, attempts, window_end), UPDATE (attempts, window_end)
    ON eduquest.login_limits TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.security_events TO __RUNTIME_ROLE__;
GRANT INSERT (account_id, event) ON eduquest.security_events TO __RUNTIME_ROLE__;
GRANT USAGE ON SEQUENCE eduquest.attempts_id_seq, eduquest.security_events_id_seq TO __RUNTIME_ROLE__;
GRANT SELECT, DELETE ON eduquest.community_questions TO __RUNTIME_ROLE__;
GRANT INSERT (author_id, topic, level, title, stem, option_a, option_b, option_c, option_d, option_e, correct, explanation)
    ON eduquest.community_questions TO __RUNTIME_ROLE__;
GRANT UPDATE (topic, level, title, stem, option_a, option_b, option_c, option_d, option_e, correct, explanation, revision)
    ON eduquest.community_questions TO __RUNTIME_ROLE__;
GRANT SELECT, INSERT ON eduquest.community_answers TO __RUNTIME_ROLE__;
GRANT SELECT, INSERT, DELETE ON eduquest.community_votes TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.community_comments TO __RUNTIME_ROLE__;
GRANT INSERT (question_id, author_id, body), UPDATE (hidden_at) ON eduquest.community_comments TO __RUNTIME_ROLE__;
GRANT USAGE ON SEQUENCE eduquest.community_questions_id_seq, eduquest.community_comments_id_seq TO __RUNTIME_ROLE__;
GRANT SELECT, INSERT ON eduquest.tutor_limits TO __RUNTIME_ROLE__;
GRANT UPDATE (window_end, requests) ON eduquest.tutor_limits TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.mentors, eduquest.mentor_slots, eduquest.mentor_bookings TO __RUNTIME_ROLE__;
GRANT INSERT (slot_id, student_id, note, rate) ON eduquest.mentor_bookings TO __RUNTIME_ROLE__;
GRANT UPDATE (student_cancelled_at) ON eduquest.mentor_bookings TO __RUNTIME_ROLE__;
GRANT USAGE ON SEQUENCE eduquest.mentor_bookings_id_seq TO __RUNTIME_ROLE__;
GRANT SELECT, INSERT, DELETE ON eduquest.quiz_bookmarks TO __RUNTIME_ROLE__;
GRANT SELECT, INSERT ON eduquest.student_profiles TO __RUNTIME_ROLE__;
GRANT UPDATE (school, class_name, program, interests, bio) ON eduquest.student_profiles TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.daily_challenges TO __RUNTIME_ROLE__;
GRANT SELECT ON eduquest.published_exam_schedule TO __RUNTIME_ROLE__;
