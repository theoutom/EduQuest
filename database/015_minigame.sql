CREATE TABLE eduquest.minigames (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    title text NOT NULL CHECK (length(trim(title)) BETWEEN 1 AND 120),
    instruction text NOT NULL CHECK (length(trim(instruction)) BETWEEN 1 AND 2000),
    explanation text NOT NULL CHECK (length(trim(explanation)) BETWEEN 1 AND 4000),
    xp integer NOT NULL CHECK (xp BETWEEN 0 AND 1000),
    audience text NOT NULL CHECK (audience IN ('all_classes', 'selected_classes')),
    created_by integer NOT NULL REFERENCES eduquest.students(id),
    archived_at timestamptz
);
CREATE TABLE eduquest.minigame_classes (
    game_id integer NOT NULL REFERENCES eduquest.minigames(id),
    class_id integer NOT NULL REFERENCES eduquest.classes(id),
    PRIMARY KEY (game_id, class_id)
);
CREATE TABLE eduquest.minigame_steps (
    game_id integer NOT NULL REFERENCES eduquest.minigames(id),
    token char(32) NOT NULL UNIQUE CHECK (token ~ '^[a-f0-9]{32}$'),
    position smallint NOT NULL CHECK (position BETWEEN 1 AND 8),
    body text NOT NULL CHECK (length(trim(body)) BETWEEN 1 AND 500),
    PRIMARY KEY (game_id, position)
);
CREATE TABLE eduquest.minigame_attempts (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    game_id integer NOT NULL REFERENCES eduquest.minigames(id),
    student_id integer NOT NULL REFERENCES eduquest.students(id),
    submission_token char(32) NOT NULL CHECK (submission_token ~ '^[a-f0-9]{32}$'),
    correct boolean NOT NULL,
    earned_xp integer NOT NULL CHECK (earned_xp >= 0),
    submitted_at timestamptz NOT NULL DEFAULT clock_timestamp(),
    UNIQUE (student_id, submission_token)
);
CREATE INDEX minigame_attempts_student_game_idx ON eduquest.minigame_attempts(student_id, game_id, id DESC);
CREATE TABLE eduquest.minigame_rewards (
    game_id integer NOT NULL REFERENCES eduquest.minigames(id),
    student_id integer NOT NULL REFERENCES eduquest.students(id),
    xp integer NOT NULL CHECK (xp >= 0),
    awarded_at timestamptz NOT NULL DEFAULT clock_timestamp(),
    PRIMARY KEY (game_id, student_id)
);
