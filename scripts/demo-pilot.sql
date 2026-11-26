-- The caller must replace the placeholder with a freshly generated Argon2id hash.
-- This one-time fixture creates only explicitly labelled demonstration records.
DO $fixture$
DECLARE
    admin_id integer; teacher_id integer; student_b integer; student_a integer;
    class_b integer; class_a integer; quiz_b integer; quiz_next integer;
    version_b integer; version_next integer; question_id integer; attempt_id integer;
    root_node integer; next_node integer; locked_node integer; root_rule integer; next_rule integer; locked_rule integer;
    quest_id integer; game_id integer; started timestamptz := clock_timestamp() - interval '1 day';
    password_hash text := '__TEACHER_PASSWORD_HASH__';
BEGIN
    PERFORM pg_advisory_xact_lock(476152004);
    IF EXISTS (SELECT 1 FROM eduquest.admin_changes WHERE action = 'demo_fixture_v1' AND target = 'demo-pilot-2026') THEN
        RETURN;
    END IF;
    IF password_hash NOT LIKE '$argon2id$%' OR length(password_hash) < 60 THEN
        RAISE EXCEPTION 'A generated Argon2id teacher password hash is required';
    END IF;
    SELECT id INTO admin_id FROM eduquest.students WHERE username = 'pilot-admin' AND role = 'admin' AND enabled;
    SELECT id INTO student_b FROM eduquest.students WHERE username = 'pilot-teman' AND role = 'student' AND enabled;
    SELECT id INTO student_a FROM eduquest.students WHERE username = 'pilot-siswa' AND role = 'student' AND enabled;
    IF admin_id IS NULL OR student_b IS NULL OR student_a IS NULL THEN
        RAISE EXCEPTION 'Expected enabled pilot accounts are unavailable';
    END IF;
    IF EXISTS (SELECT 1 FROM eduquest.students WHERE username = 'demo-guru')
       OR EXISTS (SELECT 1 FROM eduquest.quizzes WHERE slug IN ('demo-pplg-b-dasar', 'demo-pplg-b-lanjutan'))
       OR EXISTS (SELECT 1 FROM eduquest.classes WHERE academic_year = '2026/2027' AND name IN ('XI-PPLG-B (Demo)', 'XI-PPLG-A (Demo)')) THEN
        RAISE EXCEPTION 'Demonstration identifiers are already in use; no existing records were modified';
    END IF;
    SELECT c.id INTO class_b FROM eduquest.classes c JOIN eduquest.class_students cs ON cs.class_id = c.id
        WHERE c.name = '[DEMO] XI PPLG B' AND c.academic_year = '2026/2027' AND c.archived_at IS NULL AND cs.student_id = student_b;
    SELECT c.id INTO class_a FROM eduquest.classes c JOIN eduquest.class_students cs ON cs.class_id = c.id
        WHERE c.name = '[DEMO] XI PPLG A' AND c.academic_year = '2026/2027' AND c.archived_at IS NULL AND cs.student_id = student_a;
    IF class_a IS NULL AND class_b IS NULL THEN
        IF EXISTS (SELECT 1 FROM eduquest.class_students cs JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL WHERE cs.student_id IN (student_a, student_b)) THEN
            RAISE EXCEPTION 'Pilot students already have different active class memberships';
        END IF;
        INSERT INTO eduquest.classes (name, academic_year) VALUES ('XI-PPLG-B (Demo)', '2026/2027') RETURNING id INTO class_b;
        INSERT INTO eduquest.classes (name, academic_year) VALUES ('XI-PPLG-A (Demo)', '2026/2027') RETURNING id INTO class_a;
        INSERT INTO eduquest.class_students (class_id, student_id) VALUES (class_b, student_b), (class_a, student_a);
    ELSIF class_a IS NULL OR class_b IS NULL OR EXISTS (
        SELECT 1 FROM eduquest.class_students cs JOIN eduquest.classes c ON c.id = cs.class_id AND c.archived_at IS NULL
        WHERE (cs.student_id = student_a AND cs.class_id <> class_a) OR (cs.student_id = student_b AND cs.class_id <> class_b)
    ) THEN
        RAISE EXCEPTION 'Existing demo class memberships are unexpected';
    END IF;
    INSERT INTO eduquest.students (username, name, role, enabled, password_hash)
    VALUES ('demo-guru', 'Guru Demo', 'teacher', true, password_hash) RETURNING id INTO teacher_id;
    INSERT INTO eduquest.class_teachers (class_id, teacher_id) VALUES (class_b, teacher_id);

    INSERT INTO eduquest.quizzes (slug, title, summary, topic, level, attempt_limit, audience, teacher_owner)
    VALUES ('demo-pplg-b-dasar', 'Latihan Logika Pemrograman Kelas B',
        'Contoh latihan untuk mendemonstrasikan kuis, pembahasan, dan nilai kelas XI-PPLG-B.',
        'pemrograman', 'dasar', 5, 'selected_classes', teacher_id) RETURNING id INTO quiz_b;
    INSERT INTO eduquest.quiz_classes VALUES (quiz_b, class_b);
    INSERT INTO eduquest.quiz_versions (quiz_id, version, duration_seconds, passing_score, tutor_enabled)
    VALUES (quiz_b, 1, 600, 75, true) RETURNING id INTO version_b;
    INSERT INTO eduquest.questions (version_id, position, stem, snippet, explanation) VALUES
        (version_b, 1, 'Jika x bernilai 5, berapa hasil x + 1?', NULL, 'Penjumlahan satu pada 5 menghasilkan 6.'),
        (version_b, 2, 'Struktur apa yang mengeksekusi langkah ketika syarat bernilai benar?', NULL, 'Percabangan if menjalankan langkah berdasarkan hasil kondisi.'),
        (version_b, 3, 'Apa fungsi perulangan dalam program?', NULL, 'Perulangan mengerjakan langkah yang sama sesuai kondisi atau jumlah yang ditentukan.'),
        (version_b, 4, 'Apa tujuan pengujian program?', NULL, 'Pengujian membantu menemukan kesalahan dan memeriksa hasil terhadap kebutuhan.');
    INSERT INTO eduquest.options (question_id, position, label, is_correct)
    SELECT q.id, o.position, o.label, o.position = 1
    FROM eduquest.questions q
    CROSS JOIN LATERAL unnest(CASE q.position
        WHEN 1 THEN ARRAY['6', '4', '5', '7']
        WHEN 2 THEN ARRAY['Percabangan if', 'Komentar', 'Tipe data', 'Nama variabel']
        WHEN 3 THEN ARRAY['Mengulang langkah', 'Menghapus semua data', 'Mengganti bahasa program', 'Menyembunyikan hasil']
        ELSE ARRAY['Memeriksa hasil dan menemukan kesalahan', 'Menjamin tidak ada bug tanpa pemeriksaan', 'Menghapus dokumentasi', 'Mengganti kebutuhan pengguna']
    END) WITH ORDINALITY AS o(label, position)
    WHERE q.version_id = version_b;
    UPDATE eduquest.quiz_versions SET published_at = clock_timestamp() WHERE id = version_b;

    INSERT INTO eduquest.quizzes (slug, title, summary, topic, level, attempt_limit, audience, teacher_owner)
    VALUES ('demo-pplg-b-lanjutan', 'Latihan Algoritma Lanjutan Kelas B',
        'Latihan lanjutan untuk cabang kompetensi setelah dasar logika.',
        'pemrograman', 'menengah', 5, 'selected_classes', teacher_id) RETURNING id INTO quiz_next;
    INSERT INTO eduquest.quiz_classes VALUES (quiz_next, class_b);
    INSERT INTO eduquest.quiz_versions (quiz_id, version, duration_seconds, passing_score, tutor_enabled)
    VALUES (quiz_next, 1, 600, 67, true) RETURNING id INTO version_next;
    INSERT INTO eduquest.questions (version_id, position, stem, snippet, explanation) VALUES
        (version_next, 1, 'Struktur data apa yang menerapkan prinsip masuk terakhir, keluar pertama?', NULL, 'Stack menggunakan prinsip last in, first out.'),
        (version_next, 2, 'Apa yang terjadi saat kondisi while bernilai false sebelum perulangan dimulai?', NULL, 'Isi while tidak dijalankan ketika kondisinya sudah false.'),
        (version_next, 3, 'Apa langkah pertama saat memperbaiki bug yang dilaporkan?', NULL, 'Mereproduksi masalah membantu menemukan kondisi penyebabnya.');
    INSERT INTO eduquest.options (question_id, position, label, is_correct)
    SELECT q.id, o.position, o.label, o.position = 1 FROM eduquest.questions q
    CROSS JOIN LATERAL unnest(CASE q.position
        WHEN 1 THEN ARRAY['Stack', 'Queue', 'Tabel', 'Pohon']
        WHEN 2 THEN ARRAY['Isi perulangan tidak dijalankan', 'Isi perulangan selalu dijalankan sekali', 'Semua variabel dihapus', 'Program selalu gagal']
        ELSE ARRAY['Mereproduksi masalah', 'Menghapus semua pengujian', 'Mengganti seluruh aplikasi', 'Mengabaikan laporan']
    END) WITH ORDINALITY AS o(label, position) WHERE q.version_id = version_next;
    UPDATE eduquest.quiz_versions SET published_at = clock_timestamp() WHERE id = version_next;

    -- This historical result is explicitly synthetic and leaves four new attempts available.
    INSERT INTO eduquest.attempts (student_id, quiz_id, version_id, started_at, deadline, finished_at, score)
    VALUES (student_b, quiz_b, version_b, started, started + interval '10 minutes', started + interval '5 minutes', 75)
    RETURNING id INTO attempt_id;
    INSERT INTO eduquest.answers (attempt_id, version_id, question_id, option_id, doubtful, answer_updated_at)
    SELECT attempt_id, version_b, q.id, o.id, false, started + interval '2 minutes'
    FROM eduquest.questions q JOIN eduquest.options o ON o.question_id = q.id
    WHERE q.version_id = version_b AND o.position = CASE WHEN q.position = 4 THEN 2 ELSE 1 END;

    INSERT INTO eduquest.quests (title, quiz_id, starts_at, ends_at, target, xp, kind, audience, created_by)
    VALUES ('Quest Demo: Selesaikan Latihan', quiz_b, clock_timestamp() - interval '1 minute', clock_timestamp() + interval '7 days', 1, 20, 'quiz_finish', 'selected_classes', teacher_id)
    RETURNING id INTO quest_id;
    INSERT INTO eduquest.quest_classes VALUES (quest_id, class_b);
    INSERT INTO eduquest.quests (title, quiz_id, starts_at, ends_at, target, xp, kind, audience, created_by)
    VALUES ('Quest Demo: Baca Pembahasan', quiz_b, clock_timestamp() - interval '1 minute', clock_timestamp() + interval '7 days', 1, 10, 'review', 'selected_classes', teacher_id)
    RETURNING id INTO quest_id;
    INSERT INTO eduquest.quest_classes VALUES (quest_id, class_b);

    INSERT INTO eduquest.mastery_nodes (class_id, created_by) VALUES (class_b, teacher_id) RETURNING id INTO root_node;
    INSERT INTO eduquest.mastery_rules (node_id, version, title, description, topic, quiz_id, quiz_version_id, passing_score)
    VALUES (root_node, 1, 'Dasar Logika Program', 'Kenali penjumlahan, percabangan, perulangan, dan pengujian.', 'pemrograman', quiz_b, version_b, 75) RETURNING id INTO root_rule;
    INSERT INTO eduquest.mastery_nodes (class_id, created_by) VALUES (class_b, teacher_id) RETURNING id INTO next_node;
    INSERT INTO eduquest.mastery_rules (node_id, version, title, description, topic, quiz_id, quiz_version_id, passing_score)
    VALUES (next_node, 1, 'Algoritma Lanjutan', 'Latih struktur data, kondisi perulangan, dan langkah debugging.', 'pemrograman', quiz_next, version_next, 67) RETURNING id INTO next_rule;
    INSERT INTO eduquest.mastery_prerequisites (rule_id, prerequisite_node_id) VALUES (next_rule, root_node);
    INSERT INTO eduquest.mastery_nodes (class_id, created_by) VALUES (class_b, teacher_id) RETURNING id INTO locked_node;
    INSERT INTO eduquest.mastery_rules (node_id, version, title, description, topic)
    VALUES (locked_node, 1, 'Merancang Solusi', 'Kompetensi lanjutan yang dapat dilengkapi guru dengan kuis.', 'pemrograman') RETURNING id INTO locked_rule;
    INSERT INTO eduquest.mastery_prerequisites (rule_id, prerequisite_node_id) VALUES (locked_rule, next_node);

    INSERT INTO eduquest.minigames (title, instruction, explanation, xp, audience, created_by)
    VALUES ('Demo Susun Langkah: Perbaiki Bug', 'Urutkan langkah sederhana untuk menanggapi bug.',
        'Mulai dengan mereproduksi masalah, cari penyebab, lalu perbaiki dan uji ulang.', 15, 'selected_classes', teacher_id) RETURNING id INTO game_id;
    INSERT INTO eduquest.minigame_classes VALUES (game_id, class_b);
    INSERT INTO eduquest.minigame_steps (game_id, token, position, body) VALUES
        (game_id, replace(gen_random_uuid()::text, '-', ''), 1, 'Reproduksi masalah'),
        (game_id, replace(gen_random_uuid()::text, '-', ''), 2, 'Cari penyebab'),
        (game_id, replace(gen_random_uuid()::text, '-', ''), 3, 'Perbaiki dan uji ulang');
    INSERT INTO eduquest.admin_changes (actor_id, action, target) VALUES (admin_id, 'demo_fixture_v1', 'demo-pilot-2026');
END
$fixture$;
