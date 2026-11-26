ALTER TABLE eduquest.answers ADD COLUMN answer_updated_at timestamptz;
ALTER TABLE eduquest.answers ALTER COLUMN answer_updated_at SET DEFAULT clock_timestamp();

CREATE TABLE eduquest.quests (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    title text NOT NULL CHECK (length(trim(title)) BETWEEN 1 AND 120),
    quiz_id integer NOT NULL REFERENCES eduquest.quizzes(id),
    starts_at timestamptz NOT NULL,
    ends_at timestamptz NOT NULL CHECK (ends_at > starts_at),
    target integer NOT NULL CHECK (target BETWEEN 1 AND 100),
    xp integer NOT NULL CHECK (xp BETWEEN 1 AND 1000),
    kind text NOT NULL DEFAULT 'quiz_finish' CHECK (kind IN ('quiz_finish', 'review', 'answers')),
    audience text NOT NULL DEFAULT 'all_classes' CHECK (audience IN ('all_classes', 'selected_classes')),
    created_by integer REFERENCES eduquest.students(id),
    archived_at timestamptz
);
CREATE TABLE eduquest.quest_classes (
    quest_id integer NOT NULL REFERENCES eduquest.quests(id) ON DELETE CASCADE,
    class_id integer NOT NULL REFERENCES eduquest.classes(id),
    PRIMARY KEY (quest_id, class_id)
);
CREATE TABLE eduquest.quest_rewards (
    quest_id integer NOT NULL REFERENCES eduquest.quests(id),
    student_id integer NOT NULL REFERENCES eduquest.students(id),
    xp integer NOT NULL CHECK (xp > 0),
    awarded_at timestamptz NOT NULL DEFAULT clock_timestamp(),
    PRIMARY KEY (quest_id, student_id)
);
CREATE TABLE eduquest.quest_events (
    quest_id integer NOT NULL REFERENCES eduquest.quests(id),
    student_id integer NOT NULL REFERENCES eduquest.students(id),
    attempt_id integer NOT NULL REFERENCES eduquest.attempts(id),
    amount integer NOT NULL DEFAULT 1 CHECK (amount > 0),
    PRIMARY KEY (quest_id, student_id, attempt_id)
);
CREATE INDEX quests_active_quiz_idx ON eduquest.quests (quiz_id, starts_at, ends_at) WHERE archived_at IS NULL;
CREATE INDEX quest_events_attempt_idx ON eduquest.quest_events (attempt_id);
CREATE INDEX quest_rewards_student_idx ON eduquest.quest_rewards (student_id);
