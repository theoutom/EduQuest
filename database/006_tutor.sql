ALTER TABLE eduquest.quiz_versions ADD COLUMN tutor_enabled boolean NOT NULL DEFAULT false;

CREATE TABLE eduquest.tutor_limits (
    student_id integer PRIMARY KEY REFERENCES eduquest.students(id) ON DELETE CASCADE,
    window_end timestamptz NOT NULL,
    requests integer NOT NULL CHECK (requests BETWEEN 1 AND 9)
);
