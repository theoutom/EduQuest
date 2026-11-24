CREATE TABLE eduquest.daily_challenges (
    challenge_date date PRIMARY KEY,
    quiz_id integer NOT NULL REFERENCES eduquest.quizzes(id) ON DELETE CASCADE
);

CREATE INDEX daily_challenge_quiz_idx ON eduquest.daily_challenges(quiz_id);
