CREATE TABLE eduquest.quiz_bookmarks (
    student_id integer NOT NULL REFERENCES eduquest.students(id) ON DELETE CASCADE,
    quiz_id integer NOT NULL REFERENCES eduquest.quizzes(id) ON DELETE CASCADE,
    PRIMARY KEY (student_id, quiz_id)
);

CREATE INDEX quiz_bookmark_quiz_idx ON eduquest.quiz_bookmarks(quiz_id);
