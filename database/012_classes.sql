CREATE TABLE eduquest.classes (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name text NOT NULL CHECK (length(trim(name)) BETWEEN 1 AND 80),
    academic_year text NOT NULL CHECK (length(trim(academic_year)) BETWEEN 1 AND 30),
    archived_at timestamptz,
    UNIQUE (name, academic_year)
);

CREATE TABLE eduquest.class_students (
    class_id integer NOT NULL REFERENCES eduquest.classes(id),
    student_id integer NOT NULL REFERENCES eduquest.students(id) ON DELETE CASCADE,
    PRIMARY KEY (class_id, student_id)
);

ALTER TABLE eduquest.quizzes ADD COLUMN audience text NOT NULL DEFAULT 'all_classes'
    CHECK (audience IN ('all_classes', 'selected_classes'));
ALTER TABLE eduquest.quizzes ADD COLUMN teacher_owner integer REFERENCES eduquest.students(id);

CREATE TABLE eduquest.quiz_classes (
    quiz_id integer NOT NULL REFERENCES eduquest.quizzes(id) ON DELETE CASCADE,
    class_id integer NOT NULL REFERENCES eduquest.classes(id),
    PRIMARY KEY (quiz_id, class_id)
);

ALTER TABLE eduquest.students DROP CONSTRAINT students_role_check;
ALTER TABLE eduquest.students ADD CONSTRAINT students_role_check CHECK (role IN ('student', 'admin', 'teacher'));
CREATE POLICY teacher_insert ON eduquest.students FOR INSERT WITH CHECK (role = 'teacher');
CREATE POLICY teacher_update ON eduquest.students FOR UPDATE USING (role = 'teacher') WITH CHECK (role = 'teacher');
CREATE OR REPLACE FUNCTION eduquest.revoke_changed_student_access() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
BEGIN
    IF OLD.role IN ('student', 'teacher') AND (
        TG_OP = 'DELETE' OR NEW.enabled IS DISTINCT FROM OLD.enabled
        OR NEW.password_hash IS DISTINCT FROM OLD.password_hash
    ) THEN
        PERFORM pg_advisory_xact_lock(476152004);
        DELETE FROM eduquest.sessions WHERE student_id = OLD.id;
        DELETE FROM eduquest.login_limits
        WHERE bucket = 'account:' || encode(sha256(convert_to(OLD.username, 'UTF8')), 'hex');
    END IF;
    RETURN NULL;
END;
$$;
ALTER TABLE eduquest.sessions DROP CONSTRAINT sessions_issued_role_check;
ALTER TABLE eduquest.sessions ADD CONSTRAINT sessions_issued_role_check CHECK (issued_role IN ('student', 'admin', 'teacher'));

CREATE TABLE eduquest.class_teachers (
    class_id integer NOT NULL REFERENCES eduquest.classes(id),
    teacher_id integer NOT NULL REFERENCES eduquest.students(id) ON DELETE CASCADE,
    PRIMARY KEY (class_id, teacher_id)
);

CREATE FUNCTION eduquest.clear_old_class_assignments() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
BEGIN
    IF OLD.role IS DISTINCT FROM NEW.role THEN
        PERFORM pg_advisory_xact_lock(476152004);
        DELETE FROM eduquest.class_teachers WHERE teacher_id = OLD.id;
        DELETE FROM eduquest.class_students WHERE student_id = OLD.id;
    END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER clear_old_class_assignments AFTER UPDATE OF role ON eduquest.students
FOR EACH ROW EXECUTE FUNCTION eduquest.clear_old_class_assignments();

ALTER TABLE eduquest.exam_schedule ADD COLUMN audience text NOT NULL DEFAULT 'all_classes'
    CHECK (audience IN ('all_classes', 'selected_classes'));
CREATE TABLE eduquest.exam_classes (
    exam_id integer NOT NULL REFERENCES eduquest.exam_schedule(id) ON DELETE CASCADE,
    class_id integer NOT NULL REFERENCES eduquest.classes(id),
    PRIMARY KEY (exam_id, class_id)
);
CREATE OR REPLACE VIEW eduquest.published_exam_schedule AS
SELECT id, title, location, note, starts_at, ends_at, audience
FROM eduquest.exam_schedule WHERE published;
