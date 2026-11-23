CREATE TABLE eduquest.student_profiles (
    student_id integer PRIMARY KEY REFERENCES eduquest.students(id) ON DELETE CASCADE,
    school text NOT NULL CHECK (char_length(school) <= 120),
    class_name text NOT NULL CHECK (char_length(class_name) <= 40),
    program text NOT NULL CHECK (char_length(program) <= 80),
    interests text NOT NULL CHECK (char_length(interests) <= 240),
    bio text NOT NULL CHECK (char_length(bio) <= 600)
);
