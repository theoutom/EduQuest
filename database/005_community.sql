CREATE TABLE eduquest.community_questions (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    author_id integer REFERENCES eduquest.students(id) ON DELETE SET NULL,
    topic text NOT NULL REFERENCES eduquest.topics(slug),
    level text NOT NULL CHECK (level IN ('dasar', 'menengah', 'lanjutan')),
    title text NOT NULL CHECK (length(title) BETWEEN 1 AND 140),
    stem text NOT NULL CHECK (length(stem) BETWEEN 1 AND 3000),
    option_a text NOT NULL CHECK (length(option_a) BETWEEN 1 AND 400),
    option_b text NOT NULL CHECK (length(option_b) BETWEEN 1 AND 400),
    option_c text CHECK (length(option_c) BETWEEN 1 AND 400),
    option_d text CHECK (option_d IS NULL OR (length(option_d) BETWEEN 1 AND 400 AND option_c IS NOT NULL)),
    option_e text CHECK (option_e IS NULL OR (length(option_e) BETWEEN 1 AND 400 AND option_d IS NOT NULL)),
    correct text NOT NULL CHECK (CASE correct WHEN 'a' THEN true WHEN 'b' THEN true
        WHEN 'c' THEN option_c IS NOT NULL WHEN 'd' THEN option_d IS NOT NULL WHEN 'e' THEN option_e IS NOT NULL ELSE false END),
    explanation text NOT NULL CHECK (length(explanation) BETWEEN 1 AND 4000),
    status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'published', 'rejected', 'archived')),
    revision integer NOT NULL DEFAULT 1 CHECK (revision > 0),
    moderation_note text CHECK (length(moderation_note) BETWEEN 1 AND 400),
    created_at timestamptz NOT NULL DEFAULT clock_timestamp(),
    published_at timestamptz,
    CHECK ((status IN ('published', 'archived')) = (published_at IS NOT NULL))
);
CREATE INDEX community_catalog_idx ON eduquest.community_questions(published_at DESC, id DESC) WHERE status = 'published';
CREATE INDEX community_author_idx ON eduquest.community_questions(author_id, id DESC);
CREATE INDEX community_topic_idx ON eduquest.community_questions(topic);

CREATE FUNCTION eduquest.protect_community_question() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    IF OLD.published_at IS NOT NULL THEN
        IF TG_OP = 'DELETE' THEN
            RAISE EXCEPTION 'Published contribution cannot be deleted' USING ERRCODE = '23514';
        END IF;
        IF ROW(OLD.title, OLD.topic, OLD.level, OLD.stem, OLD.option_a, OLD.option_b, OLD.option_c,
               OLD.option_d, OLD.option_e, OLD.correct, OLD.explanation, OLD.published_at)
           IS DISTINCT FROM
           ROW(NEW.title, NEW.topic, NEW.level, NEW.stem, NEW.option_a, NEW.option_b, NEW.option_c,
               NEW.option_d, NEW.option_e, NEW.correct, NEW.explanation, NEW.published_at) THEN
            RAISE EXCEPTION 'Published contribution content cannot be changed' USING ERRCODE = '23514';
        END IF;
    END IF;
    IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER protect_community_question BEFORE UPDATE OR DELETE ON eduquest.community_questions
FOR EACH ROW EXECUTE FUNCTION eduquest.protect_community_question();

CREATE TABLE eduquest.community_answers (
    question_id integer NOT NULL REFERENCES eduquest.community_questions(id),
    student_id integer NOT NULL REFERENCES eduquest.students(id) ON DELETE CASCADE,
    choice text NOT NULL CHECK (choice IN ('a', 'b', 'c', 'd', 'e')),
    PRIMARY KEY (question_id, student_id)
);
CREATE INDEX community_answers_student_idx ON eduquest.community_answers(student_id);

CREATE TABLE eduquest.community_votes (
    question_id integer NOT NULL REFERENCES eduquest.community_questions(id),
    student_id integer NOT NULL REFERENCES eduquest.students(id) ON DELETE CASCADE,
    PRIMARY KEY (question_id, student_id)
);
CREATE INDEX community_votes_student_idx ON eduquest.community_votes(student_id);

CREATE TABLE eduquest.community_comments (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    question_id integer NOT NULL REFERENCES eduquest.community_questions(id),
    author_id integer REFERENCES eduquest.students(id) ON DELETE SET NULL,
    body text NOT NULL CHECK (length(body) BETWEEN 1 AND 1000),
    hidden_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT clock_timestamp()
);
CREATE INDEX community_comments_question_idx ON eduquest.community_comments(question_id, id);
CREATE INDEX community_comments_author_idx ON eduquest.community_comments(author_id);
