CREATE TABLE eduquest.mentors (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name text NOT NULL CHECK (length(name) BETWEEN 1 AND 100),
    category text NOT NULL CHECK (category IN ('pemrograman', 'basis-data', 'web', 'karier')),
    bio text NOT NULL CHECK (length(bio) BETWEEN 1 AND 1600),
    rate integer NOT NULL CHECK (rate BETWEEN 0 AND 10000000),
    verified boolean NOT NULL DEFAULT false,
    enabled boolean NOT NULL DEFAULT true
);

CREATE TABLE eduquest.mentor_slots (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    mentor_id integer NOT NULL REFERENCES eduquest.mentors(id),
    starts_at timestamptz NOT NULL,
    ends_at timestamptz NOT NULL CHECK (ends_at > starts_at),
    enabled boolean NOT NULL DEFAULT true,
    UNIQUE (mentor_id, starts_at)
);

CREATE FUNCTION eduquest.prevent_mentor_overlap() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    PERFORM pg_advisory_xact_lock(476152005, NEW.mentor_id);
    IF NEW.enabled AND EXISTS (
        SELECT 1 FROM eduquest.mentor_slots s WHERE s.mentor_id = NEW.mentor_id AND s.enabled AND s.id <> NEW.id
        AND s.starts_at < NEW.ends_at AND s.ends_at > NEW.starts_at
    ) THEN
        RAISE EXCEPTION 'Mentor slots overlap' USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END;
$$;
CREATE TRIGGER prevent_mentor_overlap BEFORE INSERT OR UPDATE ON eduquest.mentor_slots
FOR EACH ROW EXECUTE FUNCTION eduquest.prevent_mentor_overlap();

CREATE TABLE eduquest.mentor_bookings (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    slot_id integer NOT NULL REFERENCES eduquest.mentor_slots(id),
    student_id integer NOT NULL REFERENCES eduquest.students(id),
    note text NOT NULL DEFAULT '' CHECK (length(note) <= 600),
    rate integer NOT NULL CHECK (rate BETWEEN 0 AND 10000000),
    status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'rejected', 'cancelled')),
    message text CHECK (length(message) BETWEEN 1 AND 400),
    meeting_url text CHECK (length(meeting_url) <= 1000 AND meeting_url LIKE 'https://%'),
    student_cancelled_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT clock_timestamp(),
    CHECK ((status = 'confirmed') = (meeting_url IS NOT NULL))
);
CREATE UNIQUE INDEX mentor_one_active_booking_idx ON eduquest.mentor_bookings(slot_id)
WHERE status IN ('pending', 'confirmed') AND student_cancelled_at IS NULL;
CREATE INDEX mentor_booking_student_idx ON eduquest.mentor_bookings(student_id, id DESC);
