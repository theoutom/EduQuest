ALTER TABLE eduquest.quizzes ADD COLUMN archived_at timestamptz;
ALTER TABLE eduquest.quiz_versions ADD COLUMN published_at timestamptz;
UPDATE eduquest.quiz_versions SET published_at = clock_timestamp();
CREATE UNIQUE INDEX quiz_one_draft_idx ON eduquest.quiz_versions(quiz_id) WHERE published_at IS NULL;

CREATE FUNCTION eduquest.reject_published_version_change() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        IF NEW.published_at IS NOT NULL THEN
            RAISE EXCEPTION 'New versions must start as drafts' USING ERRCODE = '23514';
        END IF;
        RETURN NEW;
    END IF;
    IF OLD.published_at IS NOT NULL THEN
        RAISE EXCEPTION 'Published quiz versions are immutable' USING ERRCODE = '23514';
    END IF;
    IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
    IF NEW.published_at IS NOT NULL AND (
        NOT EXISTS (SELECT 1 FROM eduquest.questions WHERE version_id = OLD.id)
        OR EXISTS (
            SELECT q.id FROM eduquest.questions q LEFT JOIN eduquest.options o ON o.question_id = q.id
            WHERE q.version_id = OLD.id GROUP BY q.id
            HAVING count(o.id) < 2 OR count(o.id) FILTER (WHERE o.is_correct) <> 1
        )
    ) THEN
        RAISE EXCEPTION 'Publishing requires questions and exactly one key per question' USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER protect_published_version
BEFORE INSERT OR UPDATE OR DELETE ON eduquest.quiz_versions
FOR EACH ROW EXECUTE FUNCTION eduquest.reject_published_version_change();

CREATE FUNCTION eduquest.guard_quiz_content() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE parent_version integer;
BEGIN
    IF TG_TABLE_NAME = 'questions' THEN
        IF TG_OP = 'UPDATE' AND OLD.version_id <> NEW.version_id THEN
            RAISE EXCEPTION 'Quiz version cannot be changed' USING ERRCODE = '23514';
        END IF;
        parent_version := CASE WHEN TG_OP = 'INSERT' THEN NEW.version_id ELSE OLD.version_id END;
    ELSE
        IF TG_OP = 'UPDATE' AND OLD.question_id <> NEW.question_id THEN
            RAISE EXCEPTION 'Option question cannot be changed' USING ERRCODE = '23514';
        END IF;
        SELECT version_id INTO parent_version FROM eduquest.questions
        WHERE id = CASE WHEN TG_OP = 'INSERT' THEN NEW.question_id ELSE OLD.question_id END;
    END IF;
    PERFORM 1 FROM eduquest.quiz_versions WHERE id = parent_version AND published_at IS NULL FOR SHARE;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'Published quiz content is immutable' USING ERRCODE = '23514';
    END IF;
    IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER guard_question_version BEFORE INSERT OR UPDATE OR DELETE ON eduquest.questions
FOR EACH ROW EXECUTE FUNCTION eduquest.guard_quiz_content();
CREATE TRIGGER guard_option_version BEFORE INSERT OR UPDATE OR DELETE ON eduquest.options
FOR EACH ROW EXECUTE FUNCTION eduquest.guard_quiz_content();

ALTER TABLE eduquest.quiz_versions ENABLE ROW LEVEL SECURITY;
CREATE POLICY published_versions ON eduquest.quiz_versions FOR SELECT USING (published_at IS NOT NULL);
CREATE POLICY editor_versions ON eduquest.quiz_versions FOR ALL
    USING (has_column_privilege(current_user, 'eduquest.quiz_versions', 'quiz_id', 'INSERT'));

ALTER TABLE eduquest.questions ENABLE ROW LEVEL SECURITY;
CREATE POLICY published_questions ON eduquest.questions FOR SELECT USING (
    EXISTS (SELECT 1 FROM eduquest.quiz_versions v WHERE v.id = version_id AND v.published_at IS NOT NULL)
);
CREATE POLICY editor_questions ON eduquest.questions FOR ALL
    USING (has_column_privilege(current_user, 'eduquest.questions', 'version_id', 'INSERT'));

ALTER TABLE eduquest.options ENABLE ROW LEVEL SECURITY;
CREATE POLICY published_options ON eduquest.options FOR SELECT USING (
    EXISTS (SELECT 1 FROM eduquest.questions q JOIN eduquest.quiz_versions v ON v.id = q.version_id
            WHERE q.id = question_id AND v.published_at IS NOT NULL)
);
CREATE POLICY editor_options ON eduquest.options FOR ALL
    USING (has_column_privilege(current_user, 'eduquest.options', 'question_id', 'INSERT'));

CREATE FUNCTION eduquest.guard_attempt_version() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    PERFORM pg_advisory_xact_lock(476152003, NEW.quiz_id);
    IF NOT EXISTS (
        SELECT 1 FROM eduquest.quiz_versions v JOIN eduquest.quizzes q ON q.id = v.quiz_id
        WHERE v.id = NEW.version_id AND q.id = NEW.quiz_id AND v.published_at IS NOT NULL AND q.archived_at IS NULL
    ) THEN
        RAISE EXCEPTION 'New attempts require a published, available quiz' USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER guard_attempt_version BEFORE INSERT ON eduquest.attempts
FOR EACH ROW EXECUTE FUNCTION eduquest.guard_attempt_version();

ALTER TABLE eduquest.students ENABLE ROW LEVEL SECURITY;
CREATE POLICY account_read ON eduquest.students FOR SELECT USING (true);
CREATE POLICY student_insert ON eduquest.students FOR INSERT WITH CHECK (role = 'student');
CREATE POLICY student_update ON eduquest.students FOR UPDATE USING (role = 'student') WITH CHECK (role = 'student');
CREATE POLICY student_delete ON eduquest.students FOR DELETE USING (role = 'student');

CREATE FUNCTION eduquest.revoke_changed_student_access() RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = pg_catalog AS $$
BEGIN
    IF OLD.role = 'student' AND (
        TG_OP = 'DELETE' OR NEW.enabled IS DISTINCT FROM OLD.enabled
        OR NEW.password_hash IS DISTINCT FROM OLD.password_hash
    ) THEN
        DELETE FROM eduquest.sessions WHERE student_id = OLD.id;
        DELETE FROM eduquest.login_limits
        WHERE bucket = 'account:' || encode(sha256(convert_to(OLD.username, 'UTF8')), 'hex');
    END IF;
    RETURN NULL;
END;
$$;
REVOKE ALL ON FUNCTION eduquest.revoke_changed_student_access() FROM PUBLIC;
CREATE TRIGGER revoke_changed_student_access AFTER UPDATE OF enabled, password_hash OR DELETE ON eduquest.students
FOR EACH ROW EXECUTE FUNCTION eduquest.revoke_changed_student_access();

CREATE TABLE eduquest.admin_changes (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    actor_id integer NOT NULL REFERENCES eduquest.students(id),
    action text NOT NULL,
    target text NOT NULL,
    happened_at timestamptz NOT NULL DEFAULT clock_timestamp()
);
