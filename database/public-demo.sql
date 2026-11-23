CREATE TABLE eduquest.public_demo_guests (
    student_id integer PRIMARY KEY REFERENCES eduquest.students(id) ON DELETE CASCADE,
    expires_at timestamptz NOT NULL
);

CREATE INDEX public_demo_expiry_idx ON eduquest.public_demo_guests(expires_at);

CREATE FUNCTION eduquest.start_public_demo(p_token_hash text)
RETURNS boolean
LANGUAGE plpgsql SECURITY DEFINER
SET search_path = pg_catalog
AS $$
DECLARE
    guest_id integer;
    admitted integer;
    expires timestamptz := statement_timestamp() + interval '1 hour';
BEGIN
    IF p_token_hash IS NULL OR p_token_hash !~ '^[a-f0-9]{64}$' THEN
        RETURN false;
    END IF;

    PERFORM pg_advisory_xact_lock(476152009);
    DELETE FROM eduquest.attempts a USING eduquest.public_demo_guests g
        WHERE a.student_id = g.student_id AND g.expires_at <= statement_timestamp() - interval '5 minutes';
    DELETE FROM eduquest.students s USING eduquest.public_demo_guests g
        WHERE s.id = g.student_id AND g.expires_at <= statement_timestamp() - interval '5 minutes';

    IF (SELECT count(*) FROM eduquest.public_demo_guests) >= 200 THEN
        RETURN false;
    END IF;
    INSERT INTO eduquest.login_limits AS bucket (bucket, attempts, window_end)
        VALUES ('public-demo', 1, statement_timestamp() + interval '15 minutes')
    ON CONFLICT (bucket) DO UPDATE SET
        attempts = CASE WHEN bucket.window_end <= statement_timestamp() THEN 1 ELSE LEAST(bucket.attempts + 1, 51) END,
        window_end = CASE WHEN bucket.window_end <= statement_timestamp()
            THEN statement_timestamp() + interval '15 minutes' ELSE bucket.window_end END
    RETURNING attempts INTO admitted;
    IF admitted > 50 THEN
        RETURN false;
    END IF;

    INSERT INTO eduquest.students(username, name, password_hash, role)
        VALUES ('guest-' || left(p_token_hash, 32), 'Siswa Demo', 'demo-session-only', 'student')
        RETURNING id INTO guest_id;
    INSERT INTO eduquest.sessions(token_hash, student_id, expires_at, issued_role)
        VALUES (p_token_hash, guest_id, expires, 'student');
    INSERT INTO eduquest.public_demo_guests(student_id, expires_at) VALUES (guest_id, expires);
    RETURN true;
END;
$$;

REVOKE ALL ON FUNCTION eduquest.start_public_demo(text) FROM PUBLIC;
GRANT EXECUTE ON FUNCTION eduquest.start_public_demo(text) TO __RUNTIME_ROLE__;
