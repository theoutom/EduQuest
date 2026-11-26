CREATE TABLE eduquest.mastery_nodes (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    class_id integer REFERENCES eduquest.classes(id),
    created_by integer NOT NULL REFERENCES eduquest.students(id),
    created_at timestamptz NOT NULL DEFAULT clock_timestamp()
);

CREATE TABLE eduquest.mastery_rules (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    node_id integer NOT NULL REFERENCES eduquest.mastery_nodes(id),
    version integer NOT NULL CHECK (version > 0),
    title text NOT NULL CHECK (length(trim(title)) BETWEEN 1 AND 120),
    description text NOT NULL CHECK (length(trim(description)) BETWEEN 1 AND 2000),
    topic text NOT NULL REFERENCES eduquest.topics(slug),
    quiz_id integer,
    quiz_version_id integer,
    passing_score integer CHECK (passing_score BETWEEN 0 AND 100),
    created_at timestamptz NOT NULL DEFAULT clock_timestamp(),
    FOREIGN KEY (quiz_version_id, quiz_id) REFERENCES eduquest.quiz_versions(id, quiz_id),
    CHECK ((quiz_id IS NULL AND quiz_version_id IS NULL AND passing_score IS NULL) OR
           (quiz_id IS NOT NULL AND quiz_version_id IS NOT NULL AND passing_score IS NOT NULL)),
    UNIQUE (node_id, version),
    UNIQUE (id, node_id)
);

CREATE TABLE eduquest.mastery_prerequisites (
    rule_id integer NOT NULL REFERENCES eduquest.mastery_rules(id),
    prerequisite_node_id integer NOT NULL REFERENCES eduquest.mastery_nodes(id),
    PRIMARY KEY (rule_id, prerequisite_node_id)
);

CREATE TABLE eduquest.mastery_awards (
    node_id integer NOT NULL REFERENCES eduquest.mastery_nodes(id),
    rule_id integer NOT NULL,
    student_id integer NOT NULL REFERENCES eduquest.students(id),
    attempt_id integer NOT NULL REFERENCES eduquest.attempts(id),
    earned_at timestamptz NOT NULL DEFAULT clock_timestamp(),
    FOREIGN KEY (rule_id, node_id) REFERENCES eduquest.mastery_rules(id, node_id),
    UNIQUE (node_id, student_id)
);
CREATE INDEX mastery_awards_student_idx ON eduquest.mastery_awards(student_id, node_id);
CREATE INDEX mastery_rules_quiz_version_idx ON eduquest.mastery_rules(quiz_version_id);
CREATE INDEX mastery_attempt_evidence_idx ON eduquest.attempts(student_id, version_id, finished_at, id) INCLUDE (score)
    WHERE finished_at IS NOT NULL;

CREATE TABLE eduquest.mastery_processed_attempts (
    attempt_id integer PRIMARY KEY REFERENCES eduquest.attempts(id),
    processed_at timestamptz NOT NULL DEFAULT clock_timestamp()
);
