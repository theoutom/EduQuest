ALTER TABLE eduquest.students
    ADD COLUMN role text NOT NULL DEFAULT 'student' CHECK (role IN ('student', 'admin')),
    ADD COLUMN enabled boolean NOT NULL DEFAULT true,
    ALTER COLUMN password_hash DROP NOT NULL,
    ADD CONSTRAINT enabled_account_has_password CHECK (NOT enabled OR password_hash IS NOT NULL);

UPDATE eduquest.students SET enabled = false, password_hash = NULL
WHERE password_hash = '' OR username IN ('satrio', 'nadia');

DELETE FROM eduquest.sessions;
ALTER TABLE eduquest.sessions
    ADD COLUMN issued_role text NOT NULL CHECK (issued_role IN ('student', 'admin')),
    ADD COLUMN last_seen_at timestamptz NOT NULL DEFAULT clock_timestamp();
CREATE INDEX sessions_student_idx ON eduquest.sessions(student_id);

CREATE TABLE eduquest.login_limits (
    bucket text PRIMARY KEY,
    attempts integer NOT NULL CHECK (attempts > 0),
    window_end timestamptz NOT NULL
);
CREATE INDEX login_limits_expiry_idx ON eduquest.login_limits(window_end);

CREATE TABLE eduquest.security_events (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    account_id integer REFERENCES eduquest.students(id) ON DELETE SET NULL,
    event text NOT NULL CHECK (event IN ('admin_login', 'admin_logout', 'account_configured', 'account_disabled')),
    happened_at timestamptz NOT NULL DEFAULT clock_timestamp()
);
