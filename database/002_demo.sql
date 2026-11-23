INSERT INTO eduquest.students (username, name, password_hash) VALUES
    ('satrio', 'Satrio', ''),
    ('nadia', 'Nadia', '');

INSERT INTO eduquest.topics (slug, name) VALUES
    ('pemrograman', 'Pemrograman'), ('basis-data', 'Basis data'), ('web', 'Pengembangan web');

INSERT INTO eduquest.quizzes (slug, title, summary, topic, level, attempt_limit) VALUES
    ('struktur-data', 'Struktur Data & Logika Pemrograman',
     'Latih cara membaca pseudocode, mengenali struktur data, dan menganalisis operasi dasar.', 'pemrograman', 'dasar', 2),
    ('sql-relasional', 'SQL & Basis Data Relasional',
     'Tinjau cara kerja relasi tabel, penyaringan data, dan hasil operasi JOIN.', 'basis-data', 'menengah', 3),
    ('http-api', 'Dasar HTTP & REST API',
     'Kenali metode HTTP dan arti kode status yang sering dipakai di aplikasi web.', 'web', 'dasar', 2);

INSERT INTO eduquest.quiz_versions (quiz_id, version, duration_seconds, passing_score)
SELECT id, 1, CASE slug WHEN 'sql-relasional' THEN 900 ELSE 600 END, 67 FROM eduquest.quizzes ORDER BY id;

INSERT INTO eduquest.questions (version_id, position, stem, snippet, explanation)
SELECT v.id, s.position, s.stem, s.snippet, s.explanation
FROM (VALUES
    ('struktur-data', 1,
     'Jika nilai x = 14 dan y = 5, teks manakah yang dicetak oleh pseudocode berikut?',
     E'IF (x % y == 4) THEN\n    PRINT("Kondisi A")\nELSE\n    PRINT("Kondisi B")\nEND IF',
     'Operator % mengembalikan sisa pembagian. Karena 14 = 2 × 5 + 4, kondisi bernilai benar dan baris Kondisi A dijalankan.'),
    ('struktur-data', 2,
     'Sebuah editor teks menyimpan riwayat aksi. Struktur data apa yang paling sesuai untuk mengambil aksi terakhir saat pengguna menekan Undo?',
     NULL, 'Stack mengikuti urutan last in, first out. Aksi terbaru berada di bagian atas dan dibatalkan lebih dulu.'),
    ('struktur-data', 3,
     'Pada array yang elemennya berukuran tetap, berapa kompleksitas akses elemen langsung melalui indeks yang valid?',
     NULL, 'Alamat elemen dihitung dari alamat awal dan offset indeks. Banyaknya elemen tidak menambah langkah pencarian, sehingga kompleksitasnya O(1).'),
    ('sql-relasional', 1,
     'Baris manakah yang tetap muncul dalam hasil LEFT JOIN jika tabel di sebelah kanan tidak memiliki pasangan?',
     E'SELECT siswa.nama, kelas.nama\nFROM siswa\nLEFT JOIN kelas ON kelas.id = siswa.kelas_id;',
     'LEFT JOIN mempertahankan seluruh baris dari tabel kiri. Jika tidak ada pasangan, kolom dari tabel kanan bernilai NULL.'),
    ('sql-relasional', 2,
     'Klausa mana yang menyaring grup berdasarkan hasil COUNT(*) setelah GROUP BY?',
     NULL, 'HAVING memfilter hasil pengelompokan. WHERE menyaring baris sebelum grup dibuat.'),
    ('sql-relasional', 3,
     'Apa fungsi foreign key pada relasi siswa.kelas_id yang mengacu ke kelas.id?',
     NULL, 'Foreign key memastikan nilai referensi yang diisi memiliki pasangan yang sah di tabel tujuan, sesuai aturan constraint.'),
    ('http-api', 1,
     'Metode HTTP mana yang semestinya dipakai untuk membaca daftar kuis tanpa mengubah data?',
     NULL, 'GET meminta representasi suatu sumber daya. Dalam rancangan HTTP, GET bersifat safe dan tujuannya bukan mutasi data.'),
    ('http-api', 2,
     'API berhasil membuat sebuah kuis. Kode status mana yang secara khusus menyatakan sumber daya telah dibuat?',
     NULL, '201 Created secara khusus menunjukkan bahwa permintaan berhasil dan menghasilkan sumber daya baru.'),
    ('http-api', 3,
     'Siswa terautentikasi membuka ID kuis yang tidak ditemukan. Status HTTP mana yang sesuai?',
     NULL, '404 Not Found menunjukkan bahwa sumber daya yang diminta tidak ditemukan.')
) AS s(slug, position, stem, snippet, explanation)
JOIN eduquest.quizzes q ON q.slug = s.slug
JOIN eduquest.quiz_versions v ON v.quiz_id = q.id AND v.version = 1
ORDER BY q.id, s.position;

INSERT INTO eduquest.options (question_id, position, label, is_correct)
SELECT q.id, o.position, o.label, o.position = s.correct
FROM (VALUES
    ('struktur-data', 1, 1, ARRAY['Kondisi A', 'Kondisi B', 'Terjadi pembagian dengan nol', 'Tidak ada teks yang dicetak']),
    ('struktur-data', 2, 1, ARRAY['Stack', 'Queue', 'Set tanpa urutan', 'Graf berarah']),
    ('struktur-data', 3, 2, ARRAY['O(n)', 'O(1)', 'O(log n)', 'O(n²)']),
    ('sql-relasional', 1, 2, ARRAY['Hanya baris kelas tanpa siswa', 'Baris siswa dengan kolom dari kelas bernilai NULL', 'Tidak ada baris tanpa pasangan', 'Semua baris dari kedua tabel']),
    ('sql-relasional', 2, 3, ARRAY['ORDER BY', 'WHERE', 'HAVING', 'LIMIT']),
    ('sql-relasional', 3, 1, ARRAY['Menjaga integritas referensi antartabel', 'Mengurutkan baris berdasar kelas', 'Menyembunyikan data siswa', 'Mengganti fungsi primary key']),
    ('http-api', 1, 2, ARRAY['POST', 'GET', 'PATCH', 'DELETE']),
    ('http-api', 2, 3, ARRAY['200 OK', '204 No Content', '201 Created', '304 Not Modified']),
    ('http-api', 3, 1, ARRAY['404 Not Found', '401 Unauthorized', '201 Created', '500 Internal Server Error'])
) AS s(slug, number, correct, labels)
JOIN eduquest.quizzes quiz ON quiz.slug = s.slug
JOIN eduquest.quiz_versions v ON v.quiz_id = quiz.id AND v.version = 1
JOIN eduquest.questions q ON q.version_id = v.id AND q.position = s.number
CROSS JOIN LATERAL unnest(s.labels) WITH ORDINALITY AS o(label, position);
