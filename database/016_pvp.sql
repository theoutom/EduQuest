CREATE TABLE eduquest.pvp_matches (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    class_id integer NOT NULL REFERENCES eduquest.classes(id),
    quiz_id integer NOT NULL REFERENCES eduquest.quizzes(id),
    version_id integer NOT NULL,
    challenger_id integer NOT NULL REFERENCES eduquest.students(id),
    opponent_id integer NOT NULL REFERENCES eduquest.students(id),
    state text NOT NULL DEFAULT 'pending' CHECK (state IN ('pending', 'running', 'finished', 'cancelled')),
    created_at timestamptz NOT NULL DEFAULT clock_timestamp(),
    invitation_expires_at timestamptz NOT NULL DEFAULT (clock_timestamp() + interval '5 minutes'),
    started_at timestamptz,
    deadline timestamptz,
    challenger_finished_at timestamptz,
    opponent_finished_at timestamptz,
    CHECK (challenger_id <> opponent_id),
    CHECK ((started_at IS NULL) = (deadline IS NULL)),
    CHECK (deadline IS NULL OR deadline > started_at),
    FOREIGN KEY (version_id, quiz_id) REFERENCES eduquest.quiz_versions(id, quiz_id)
);
CREATE INDEX pvp_matches_challenger_idx ON eduquest.pvp_matches (challenger_id, id DESC);
CREATE INDEX pvp_matches_opponent_idx ON eduquest.pvp_matches (opponent_id, id DESC);
CREATE TABLE eduquest.pvp_answers (
    match_id integer NOT NULL REFERENCES eduquest.pvp_matches(id),
    student_id integer NOT NULL REFERENCES eduquest.students(id),
    question_id integer NOT NULL REFERENCES eduquest.questions(id),
    option_id integer NOT NULL,
    answered_at timestamptz NOT NULL DEFAULT clock_timestamp(),
    PRIMARY KEY (match_id, student_id, question_id),
    FOREIGN KEY (option_id, question_id) REFERENCES eduquest.options(id, question_id)
);
