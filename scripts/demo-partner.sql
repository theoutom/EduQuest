-- The caller replaces the placeholder with a freshly generated Argon2id hash.
DO $fixture$
DECLARE
    partner_id integer; class_id integer; admin_id integer;
    password_hash text := '__PARTNER_PASSWORD_HASH__';
BEGIN
    PERFORM pg_advisory_xact_lock(476152004);
    IF EXISTS (SELECT 1 FROM eduquest.admin_changes WHERE action = 'demo_partner_v1' AND target = 'demo-pilot-2026') THEN RETURN; END IF;
    IF password_hash NOT LIKE '$argon2id$%' OR length(password_hash) < 60 THEN RAISE EXCEPTION 'A generated Argon2id hash is required'; END IF;
    IF NOT EXISTS (SELECT 1 FROM eduquest.admin_changes WHERE action = 'demo_fixture_v1' AND target = 'demo-pilot-2026')
       OR EXISTS (SELECT 1 FROM eduquest.students WHERE username = 'demo-partner') THEN
        RAISE EXCEPTION 'Demo fixture is missing or username is already in use';
    END IF;
    SELECT id INTO admin_id FROM eduquest.students WHERE username = 'pilot-admin' AND role = 'admin' AND enabled;
    SELECT qc.class_id INTO class_id FROM eduquest.quiz_classes qc JOIN eduquest.quizzes q ON q.id = qc.quiz_id
        JOIN eduquest.classes c ON c.id = qc.class_id AND c.archived_at IS NULL WHERE q.slug = 'demo-pplg-b-dasar';
    IF admin_id IS NULL OR class_id IS NULL THEN RAISE EXCEPTION 'Demo class is unavailable'; END IF;
    INSERT INTO eduquest.students (username, name, role, enabled, password_hash)
        VALUES ('demo-partner', 'Teman Demo B', 'student', true, password_hash) RETURNING id INTO partner_id;
    INSERT INTO eduquest.class_students (class_id, student_id) VALUES (class_id, partner_id);
    INSERT INTO eduquest.admin_changes (actor_id, action, target) VALUES (admin_id, 'demo_partner_v1', 'demo-pilot-2026');
END
$fixture$;
