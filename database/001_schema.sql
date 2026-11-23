CREATE SCHEMA eduquest;
REVOKE ALL ON SCHEMA eduquest FROM PUBLIC;

CREATE TABLE eduquest.students (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username text NOT NULL UNIQUE,
    name text NOT NULL,
    password_hash text NOT NULL
);

CREATE TABLE eduquest.sessions (
    token_hash text PRIMARY KEY,
    student_id integer NOT NULL REFERENCES eduquest.students(id) ON DELETE CASCADE,
    expires_at timestamptz NOT NULL
);
CREATE INDEX sessions_expiry_idx ON eduquest.sessions(expires_at);

CREATE TABLE eduquest.topics (
    slug text PRIMARY KEY,
    name text NOT NULL
);

CREATE TABLE eduquest.quizzes (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    slug text NOT NULL UNIQUE,
    title text NOT NULL,
    summary text NOT NULL,
    topic text NOT NULL REFERENCES eduquest.topics(slug),
    level text NOT NULL CHECK (level IN ('dasar', 'menengah', 'lanjutan')),
    attempt_limit integer NOT NULL CHECK (attempt_limit > 0)
);

CREATE TABLE eduquest.quiz_versions (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    quiz_id integer NOT NULL REFERENCES eduquest.quizzes(id),
    version integer NOT NULL CHECK (version > 0),
    duration_seconds integer NOT NULL CHECK (duration_seconds > 0),
    passing_score integer NOT NULL CHECK (passing_score BETWEEN 0 AND 100),
    UNIQUE (quiz_id, version),
    UNIQUE (id, quiz_id)
);

CREATE TABLE eduquest.questions (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    version_id integer NOT NULL REFERENCES eduquest.quiz_versions(id),
    position integer NOT NULL CHECK (position > 0),
    stem text NOT NULL,
    snippet text,
    explanation text NOT NULL,
    UNIQUE (version_id, position),
    UNIQUE (id, version_id)
);

CREATE TABLE eduquest.options (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    question_id integer NOT NULL REFERENCES eduquest.questions(id),
    position integer NOT NULL CHECK (position BETWEEN 1 AND 5),
    label text NOT NULL,
    is_correct boolean NOT NULL DEFAULT false,
    UNIQUE (question_id, position),
    UNIQUE (id, question_id)
);
CREATE UNIQUE INDEX one_correct_option_idx ON eduquest.options(question_id) WHERE is_correct;

CREATE TABLE eduquest.attempts (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    student_id integer NOT NULL REFERENCES eduquest.students(id),
    quiz_id integer NOT NULL REFERENCES eduquest.quizzes(id),
    version_id integer NOT NULL,
    started_at timestamptz NOT NULL,
    deadline timestamptz NOT NULL CHECK (deadline > started_at),
    finished_at timestamptz CHECK (finished_at BETWEEN started_at AND deadline),
    score integer CHECK (score BETWEEN 0 AND 100),
    CHECK ((finished_at IS NULL) = (score IS NULL)),
    FOREIGN KEY (version_id, quiz_id) REFERENCES eduquest.quiz_versions(id, quiz_id),
    UNIQUE (id, version_id)
);
CREATE UNIQUE INDEX one_active_attempt_idx ON eduquest.attempts(student_id, quiz_id) WHERE finished_at IS NULL;
CREATE INDEX attempts_history_idx ON eduquest.attempts(student_id, quiz_id, id DESC);

CREATE TABLE eduquest.answers (
    attempt_id integer NOT NULL,
    version_id integer NOT NULL,
    question_id integer NOT NULL,
    option_id integer,
    doubtful boolean NOT NULL DEFAULT false,
    PRIMARY KEY (attempt_id, question_id),
    FOREIGN KEY (attempt_id, version_id) REFERENCES eduquest.attempts(id, version_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id, version_id) REFERENCES eduquest.questions(id, version_id),
    FOREIGN KEY (option_id, question_id) REFERENCES eduquest.options(id, question_id)
);
