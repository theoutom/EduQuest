CREATE TABLE eduquest.exam_schedule (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    title text NOT NULL CHECK (length(title) BETWEEN 1 AND 120),
    location text NOT NULL CHECK (length(location) <= 120),
    note text NOT NULL CHECK (length(note) <= 600),
    starts_at timestamptz NOT NULL,
    ends_at timestamptz NOT NULL CHECK (ends_at > starts_at),
    published boolean NOT NULL DEFAULT false
);

CREATE INDEX exam_schedule_published_start_idx ON eduquest.exam_schedule(starts_at, id) WHERE published;

CREATE VIEW eduquest.published_exam_schedule AS
SELECT id, title, location, note, starts_at, ends_at
FROM eduquest.exam_schedule WHERE published;
