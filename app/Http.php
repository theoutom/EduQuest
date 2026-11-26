<?php

declare(strict_types=1);

final class Http
{
    use MinigameHttp;
    private const SESSION = 'eduquest_session';
    private const CSRF = 'eduquest_csrf';
    private const LEVELS = ['dasar' => 'Dasar', 'menengah' => 'Menengah', 'lanjutan' => 'Lanjutan'];
    private const FILTERS = ['semua' => 'Semua', 'benar' => 'Benar', 'salah' => 'Salah', 'kosong' => 'Kosong'];
    private const QUIZ_STATES = ['belum' => 'Belum selesai', 'aktif' => 'Sedang dikerjakan', 'selesai' => 'Sudah selesai', 'tersimpan' => 'Tersimpan'];

    private ?array $student = null;
    private string $csrf;
    private string $path;
    private string $method;
    private bool $json;
    private QuizService $quiz;
    private Auth $auth;

    public function __construct(private PDO $db)
    {
        $this->path = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        $this->quiz = new QuizService($db);
        $this->auth = new Auth($db);
        $token = $this->readToken(self::SESSION);
        $this->student = $token === null ? null : $this->auth->current($token);
        $this->csrf = $this->student === null
            ? ($this->readToken(self::CSRF) ?? $this->newCsrf())
            : hash_hmac('sha256', 'eduquest:csrf', $token);
    }

    public function run(): void
    {
        if ($this->method !== 'GET' && $this->method !== 'POST') {
            header('Allow: GET, POST');
            throw new QuizProblem(405, 'Metode permintaan tidak tersedia.');
        }
        if ($this->method === 'POST' && (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site'
            || !hash_equals($this->csrf, $this->input($_POST, 'csrf')))) {
            throw new QuizProblem(403, 'Formulir sudah kedaluwarsa. Muat ulang halaman dan coba lagi.');
        }
        if (publicDemo() && !$this->publicDemoRoute()) {
            throw new QuizProblem(403, 'Demo publik menyediakan latihan kuis dan pembahasan.');
        }
        if ($this->path === '/masuk') {
            $this->login();
            return;
        }
        if ($this->student === null) {
            if ($this->json) {
                throw new QuizProblem(401, 'Sesi masuk berakhir. Silakan masuk kembali.');
            }
            $this->redirect('/masuk');
            return;
        }
        $student = (int) $this->student['id'];
        if ($this->path === '/keluar' && $this->method === 'POST') {
            $this->logout();
            return;
        }
        if ($this->student['role'] === 'teacher') {
            if ($this->path === '/' && $this->method === 'GET') {
                $this->redirect('/guru');
                return;
            }
            if ($this->path === '/guru' && $this->method === 'GET') {
                $this->page('teacher-home', 'Ruang Guru', ['classes' => (new ClassService($this->db))->teacherClasses($student)]);
                return;
            }
            if ($this->path === '/guru/quest') {
                $this->quests($student, true);
                return;
            }
            if ($this->path === '/guru/minigame') {
                $this->minigameManage($student, true);
                return;
            }
            if ($this->path === '/guru/mastery') {
                $this->masteryManage($student, true);
                return;
            }
            if (preg_match('~^/guru/kuis(?:/(\d{1,10}))?$~D', $this->path, $match)) {
                $this->teacherQuiz($student, isset($match[1]) ? $this->positive($match[1], 404) : null);
                return;
            }
            throw new QuizProblem(403, 'Halaman ini tidak tersedia untuk guru.');
        }
        if ($this->path === '/admin' || str_starts_with($this->path, '/admin/')) {
            if ($this->student['role'] !== 'admin') {
                throw new QuizProblem(403, 'Halaman ini hanya tersedia untuk admin.');
            }
            if ($this->path === '/admin/quest') {
                $this->quests($student, false);
                return;
            }
            if ($this->path === '/admin/minigame') {
                $this->minigameManage($student, false);
                return;
            }
            if ($this->path === '/admin/mastery') {
                $this->masteryManage($student, false);
                return;
            }
            if (preg_match('~^/admin/kelas(?:/(\d{1,10}))?$~D', $this->path, $match)) {
                $this->classes(isset($match[1]) ? $this->positive($match[1], 404) : null);
                return;
            }
            if (preg_match('~^/admin/komunitas(?:/(\d{1,10}))?$~D', $this->path, $match)) {
                $this->communityModeration($student, isset($match[1]) ? $this->positive($match[1], 404) : null);
                return;
            }
            if (preg_match('~^/admin/mentor(?:/(sesi|\d{1,10}))?(?:/(\d{1,10}))?$~D', $this->path, $match)) {
                $this->mentorAdmin($student, $match[1] ?? '', $match[2] ?? '');
                return;
            }
            if ($this->path !== '/admin') {
                $this->adminArea($student);
                return;
            }
            if ($this->method !== 'GET') {
                header('Allow: GET');
                throw new QuizProblem(405, 'Halaman administrasi ini hanya dapat dibaca.');
            }
            $this->admin();
            return;
        }
        if ($this->student['role'] === 'admin') {
            if ($this->method === 'GET' && $this->path === '/') {
                $this->redirect('/admin');
                return;
            }
            throw new QuizProblem(403, 'Area ini hanya tersedia untuk siswa.');
        }
        if ($this->minigameStudent($student)) { return; }
        if ($this->path === '/mastery' && $this->method === 'GET') {
            $this->page('mastery-tree', 'Mastery Tree', ['items' => (new MasteryService($this->db))->visible($student)]);
            return;
        }
        if (preg_match('~^/arena(?:/(\d{1,10}))?$~D', $this->path, $match)) {
            $id = isset($match[1]) ? $this->positive($match[1], 404) : null;
            (new PvpController($this->db))->run($student, $id, $this->method, $_POST,
                fn ($view, $title, $data) => $this->page($view, $title, $data),
                fn ($url) => $this->redirect($url));
            return;
        }
        if ($this->method === 'GET' && $this->path === '/') {
            $items = $this->quiz->catalog($student);
            $active = array_values(array_filter($items, static fn (array $item): bool => $item['active_id'] !== null));
            $featured = $active[0] ?? $items[0] ?? null;
            $this->page('dashboard', 'Dashboard', compact('items', 'featured') + [
                'ongoing' => $this->quiz->activeSessions($student, (int) ($featured['active_id'] ?? 0)),
                'progress' => $this->quiz->progress($student),
                'streak' => $this->quiz->streak($student),
                'challenge' => $this->quiz->dailyChallenge($student, $items),
                'quests' => (new QuestService($this->db))->visible($student),
                'questXp' => (new QuestService($this->db))->totalXp($student),
                'minigameXp' => (new MinigameService($this->db))->totalXp($student),
                'exams' => publicDemo() ? [] : $this->quiz->upcomingExams(3, 0, $student),
                'activity' => $this->quiz->weeklyActivity($student),
                'mentor' => publicDemo() ? null : (new Mentor($this->db))->nextSession($student),
            ]);
            return;
        }
        if ($this->method === 'GET' && $this->path === '/kuis') {
            $filters = $this->catalogFilters();
            $items = $this->quiz->catalog($student, $filters['q'], $filters['topik'], $filters['level']);
            $bookmarks = array_fill_keys($this->quiz->bookmarks($student), true);
            foreach ($items as &$item) {
                $item['state'] = $item['active_id'] !== null ? 'aktif' : ($item['has_finished'] ? 'selesai' : 'belum');
                $item['bookmarked'] = isset($bookmarks[$item['id']]);
            }
            unset($item);
            $items = array_filter($items, static fn (array $item): bool => match ($filters['status']) {
                '' => true,
                'tersimpan' => $item['bookmarked'],
                default => $item['state'] === $filters['status'],
            });
            $this->page('catalog', 'Daftar Kuis', compact('items', 'filters') + [
                'topics' => $this->quiz->topics(), 'levels' => self::LEVELS, 'states' => self::QUIZ_STATES,
                'query' => http_build_query(array_filter($filters, static fn (string $value): bool => $value !== '')),
            ]);
            return;
        }
        if ($this->method === 'POST' && preg_match('~^/kuis/([a-z0-9-]+)/simpanan$~D', $this->path, $match)) {
            $saved = $this->input($_POST, 'saved');
            if (!in_array($saved, ['0', '1'], true)) {
                throw new QuizProblem(422, 'Pilih tindakan simpan atau hapus simpanan.');
            }
            $this->quiz->setBookmark($student, $match[1], $saved === '1');
            $query = http_build_query(array_filter($this->catalogFilters(), static fn (string $value): bool => $value !== ''));
            $this->redirect('/kuis' . ($query === '' ? '' : "?$query"));
            return;
        }
        if (preg_match('~^/kuis/([a-z0-9-]+)(/mulai)?$~D', $this->path, $match)) {
            if ($this->method === 'POST' && isset($match[2])) {
                $attempt = $this->quiz->start($student, $match[1]);
                $this->redirect('/sesi/' . $attempt['id']);
                return;
            }
            if ($this->method === 'GET' && !isset($match[2])) {
                $item = $this->quiz->overview($student, $match[1]);
                $this->page('overview', $item['title'], ['item' => $item, 'levels' => self::LEVELS]);
                return;
            }
        }
        if (preg_match('~^/sesi/(\d{1,10})/tutor$~D', $this->path, $match)) {
            $this->quizTutor($student, $this->positive($match[1], 404));
            return;
        }
        if (preg_match('~^/sesi/(\d{1,10})(?:/(jawaban|kumpulkan))?$~D', $this->path, $match)) {
            $id = $this->positive($match[1], 404);
            $this->exercise($student, $id, $match[2] ?? '');
            return;
        }
        if ($this->method === 'POST' && preg_match('~^/hasil/(\d{1,10})/selesai-baca$~D', $this->path, $match)) {
            $id = $this->positive($match[1], 404);
            $this->quiz->confirmReview($student, $id);
            $this->redirect("/hasil/$id?dibaca=1");
            return;
        }
        if ($this->method === 'GET' && preg_match('~^/hasil/(\d{1,10})$~D', $this->path, $match)) {
            $review = $this->quiz->review($student, $this->positive($match[1], 404));
            $print = $this->input($_GET, 'cetak') === '1';
            $filter = $print ? 'semua' : $this->input($_GET, 'status', 'semua');
            $this->page('review', 'Hasil & Pembahasan', compact('review', 'print') + [
                'filter' => isset(self::FILTERS[$filter]) ? $filter : 'semua', 'labels' => self::FILTERS,
                'reviewConfirmed' => $this->input($_GET, 'dibaca') === '1',
            ]);
            return;
        }
        if ($this->method === 'GET' && $this->path === '/jadwal') {
            $number = $this->communityPage('hal');
            $rows = $this->quiz->upcomingExams(21, ($number - 1) * 20, $student);
            $this->page('exam-schedule', 'Jadwal Ujian', compact('number') + [
                'exams' => array_slice($rows, 0, 20), 'hasNext' => count($rows) > 20,
            ]);
            return;
        }
        if ($this->path === '/profil') {
            $profile = new StudentProfile($this->db);
            if ($this->method === 'POST') {
                $profile->save($student, $_POST);
                $this->redirect('/profil?tersimpan=1');
                return;
            }
            $this->page('profile', 'Profil dan Riwayat', [
                'details' => publicDemo() ? [] : $profile->details($student),
                'history' => $this->quiz->history($student),
                'progress' => $this->quiz->progress($student),
                'saved' => $this->input($_GET, 'tersimpan') === '1',
            ]);
            return;
        }
        if (preg_match('~^/komunitas/(\d{1,10})/tutor$~D', $this->path, $match)) {
            $id = $this->positive($match[1], 404);
            $context = (new Community($this->db))->tutorContext($id);
            $this->tutorPage($student, $context, "/komunitas/$id/tutor", "/komunitas/$id");
            return;
        }
        if (preg_match('~^/komunitas(?:/(buat|saya|\d{1,10}))?$~D', $this->path, $match)) {
            $this->community($student, $match[1] ?? '');
            return;
        }
        if (preg_match('~^/mentor(?:/(sesi|\d{1,10}))?$~D', $this->path, $match)) {
            $this->mentorStudent($student, $match[1] ?? '');
            return;
        }
        throw new QuizProblem(404, 'Halaman tidak ditemukan.');
    }

    public function error(QuizProblem $problem): void
    {
        http_response_code($problem->status);
        if ($this->json) {
            $this->respond(['message' => $problem->getMessage()]);
            return;
        }
        $this->page('error', 'Permintaan belum dapat diproses', ['message' => $problem->getMessage()]);
    }

    private function catalogFilters(): array
    {
        $search = $this->input($_GET, 'q');
        if (!mb_check_encoding($search, 'UTF-8') || str_contains($search, "\0")) {
            throw new QuizProblem(422, 'Kata pencarian tidak valid.');
        }
        $status = $this->input($_GET, 'status');
        $level = $this->input($_GET, 'level');
        $topic = $this->input($_GET, 'topik');
        return [
            'q' => mb_substr(trim($search), 0, 120),
            'topik' => preg_match('/^[a-z0-9-]{1,80}$/D', $topic) ? $topic : '',
            'level' => isset(self::LEVELS[$level]) ? $level : '',
            'status' => isset(self::QUIZ_STATES[$status]) ? $status : '',
        ];
    }

    private function quizTutor(int $student, int $id): void
    {
        $attempt = $this->quiz->attempt($student, $id);
        if ($attempt['score'] !== null) {
            throw new QuizProblem(409, 'Latihan ini sudah berakhir. Buka pembahasan untuk memeriksa jawaban.');
        }
        if (!$attempt['tutor_enabled']) {
            throw new QuizProblem(403, 'Tutor tersedia untuk kuis yang ditandai sebagai latihan.');
        }
        $position = $this->method === 'POST'
            ? $this->positive($this->input($_POST, 'position'))
            : $this->positive($this->input($_GET, 'soal', '1'));
        $current = $attempt['questions'][$position - 1] ?? throw new QuizProblem(404, 'Soal latihan tidak ditemukan.');
        $this->tutorPage($student, ['topic' => $attempt['topic'], 'stem' => $current['stem']],
            "/sesi/$id/tutor", "/sesi/$id?soal=$position", $position,
            fn (): bool => $this->quiz->attempt($student, $id)['score'] === null,
        );
    }

    private function tutorPage(int $student, array $context, string $tutorEndpoint, string $back, ?int $tutorPosition = null, ?callable $stillActive = null): void
    {
        $hint = null;
        if ($this->method === 'POST') {
            $hint = (new Tutor($this->db))->hint($student, $context['topic'], $context['stem'], $this->input($_POST, 'intent'));
            if ($stillActive !== null && !$stillActive()) {
                throw new QuizProblem(409, 'Latihan ini sudah berakhir. Buka pembahasan untuk memeriksa jawaban.');
            }
            if ($this->json) {
                $this->respond($hint);
                return;
            }
        }
        $this->page('tutor', 'Tutor latihan', compact('tutorEndpoint', 'tutorPosition', 'back', 'hint'));
    }

    private function exercise(int $student, int $id, string $action): void
    {
        $base = "/sesi/$id";
        if ($this->method === 'POST' && $action === 'jawaban') {
            $option = $this->input($_POST, 'option');
            $question = $this->positive($this->input($_POST, 'question'));
            $target = $this->input($_POST, 'target');
            $destination = '';
            if (!$this->json) {
                $destination = match ($target) {
                    'kumpulkan' => "$base/kumpulkan?soal=" . $this->positive($this->input($_POST, 'position', '1')),
                    'tutor' => "$base/tutor?soal=" . $this->positive($this->input($_POST, 'position')),
                    default => "$base?soal=" . $this->positive($target),
                };
            }
            $this->quiz->save(
                $student,
                $id,
                $question,
                $option === '' ? null : $this->positive($option),
                $this->input($_POST, 'doubtful') === '1',
            );
            if ($this->json) {
                $this->respond(['saved' => true]);
                return;
            }
            $this->redirect($destination);
            return;
        }
        if ($this->method === 'POST' && $action === 'kumpulkan') {
            $this->quiz->submit($student, $id);
            $this->redirect("/hasil/$id");
            return;
        }
        if ($this->method !== 'GET' || ($action !== '' && $action !== 'kumpulkan')) {
            throw new QuizProblem(404, 'Halaman tidak ditemukan.');
        }
        $attempt = $this->quiz->attempt($student, $id);
        if ($attempt['score'] !== null) {
            if ($this->json) {
                $this->respond(['location' => "/hasil/$id"]);
            } else {
                $this->redirect("/hasil/$id");
            }
            return;
        }
        $position = $this->positive($this->input($_GET, 'soal', '1'), 404);
        $questions = $attempt['questions'];
        $current = $questions[$position - 1] ?? null;
        if ($current === null) {
            throw new QuizProblem(404, 'Nomor soal tidak ditemukan.');
        }
        $blank = count(array_filter($questions, static fn (array $question): bool => $question['selected_id'] === null));
        if ($action === 'kumpulkan' && $this->json) {
            $this->respond(['blank' => $blank]);
            return;
        }
        $view = $action === 'kumpulkan' ? 'confirmation' : 'exercise';
        $this->page($view, $view === 'confirmation' ? 'Kumpulkan kuis' : $attempt['title'], compact(
            'attempt', 'base', 'current', 'position', 'blank',
        ));
    }

    private function publicDemoRoute(): bool
    {
        if ($this->student !== null && $this->student['role'] !== 'student') {
            return false;
        }
        $pattern = $this->method === 'POST'
            ? '~^/(?:masuk|keluar|kuis/[a-z0-9-]+/mulai|sesi/\d+/(?:jawaban|kumpulkan))$~D'
            : '~^/(?:masuk|profil|kuis(?:/[a-z0-9-]+)?|sesi/\d+(?:/kumpulkan)?|hasil/\d+)?$~D';
        return preg_match($pattern, $this->path) === 1;
    }

    private function login(): void
    {
        if ($this->student !== null) {
            $this->redirect($this->student['role'] === 'admin' ? '/admin' : '/');
            return;
        }
        $message = '';
        if ($this->method === 'POST') {
            $login = publicDemo() ? $this->auth->startDemo() : $this->auth->login(
                Auth::username($this->input($_POST, 'username')),
                $this->input($_POST, 'password'),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            );
            if ($login !== null) {
                $this->cookie(self::SESSION, $login['token'], time() + $login['duration']);
                $this->cookie(self::CSRF, '', time() - 3600);
                $this->redirect($login['role'] === 'admin' ? '/admin' : '/');
                return;
            }
            http_response_code(401);
            $message = 'Nama pengguna atau kata sandi salah.';
        }
        $this->page('login', 'Masuk ke EduQuest', compact('message'));
    }

    private function logout(): void
    {
        $token = $this->readToken(self::SESSION);
        if ($token !== null) {
            $this->auth->logout($token);
        }
        $this->cookie(self::SESSION, '', time() - 3600);
        $this->newCsrf();
        $this->redirect('/masuk');
    }

    private function admin(): void
    {
        $stats = $this->db->query(<<<'SQL'
            SELECT
                (SELECT count(*) FROM eduquest.students WHERE role = 'student' AND enabled) AS active_students,
                (SELECT count(*) FROM eduquest.quizzes q WHERE q.archived_at IS NULL AND EXISTS (
                    SELECT 1 FROM eduquest.quiz_versions v WHERE v.quiz_id = q.id AND v.published_at IS NOT NULL
                )) AS quizzes,
                (SELECT count(*) FROM eduquest.attempts WHERE finished_at IS NOT NULL) AS completed
            SQL)->fetch();
        $events = $this->db->query(<<<'SQL'
            SELECT event, happened_at FROM eduquest.security_events ORDER BY id DESC LIMIT 12
            SQL);
        $this->page('admin', 'Administrasi EduQuest', ['stats' => $stats, 'events' => $events->fetchAll()]);
    }

    private function adminArea(int $actor): void
    {
        if (!preg_match('~^/admin/(topik|kuis|siswa|guru|riwayat|tantangan|jadwal)(?:/(\d{1,10}))?$~D', $this->path, $match)) {
            throw new QuizProblem(404, 'Halaman administrasi tidak ditemukan.');
        }
        $section = $match[1];
        $id = isset($match[2]) ? $this->positive($match[2], 404) : null;
        if ($id !== null && in_array($section, ['topik', 'riwayat', 'tantangan'], true)) {
            throw new QuizProblem(404, 'Halaman administrasi tidak ditemukan.');
        }
        if ($section === 'riwayat' && $this->method !== 'GET') {
            header('Allow: GET');
            throw new QuizProblem(405, 'Riwayat hanya dapat dibaca.');
        }
        $store = new AdminStore(adminDatabase(), $actor);
        $action = $this->input($_POST, 'action');
        $message = '';
        $submitted = [];
        if ($this->method === 'POST') {
            try {
                if ($section === 'kuis' && $id !== null && $action === 'audience') {
                    $audience = $this->input($_POST, 'audience');
                    $values = $audience === 'all_classes' ? [] : ($_POST['classes'] ?? []);
                    $values = is_array($values) ? $values : [$values];
                    $ids = array_map(fn ($value) => $this->positive(is_string($value) ? $value : ''), $values);
                    (new ClassService(adminDatabase(), $actor))->setQuizAudience($id, $audience, $ids);
                    $this->redirect("/admin/kuis/$id?saved=1");
                    return;
                }
                if ($section === 'jadwal' && $id !== null && $action === 'audience') {
                    $audience = $this->input($_POST, 'audience');
                    $values = $audience === 'all_classes' ? [] : ($_POST['classes'] ?? []);
                    $values = is_array($values) ? $values : [$values];
                    $ids = array_map(fn ($value) => $this->positive(is_string($value) ? $value : ''), $values);
                    (new ClassService(adminDatabase(), $actor))->setExamAudience($id, $audience, $ids);
                    $this->redirect("/admin/jadwal/$id?saved=1");
                    return;
                }
                $destination = $this->adminMutation($store, $section, $id, $action);
                $this->redirect($destination . '?saved=1');
                return;
            } catch (QuizProblem $problem) {
                if (!in_array($problem->status, [409, 422], true)) {
                    throw $problem;
                }
                http_response_code($problem->status);
                $message = $problem->getMessage();
                $submitted = $_POST;
                unset($submitted['password'], $submitted['csrf']);
            }
        }
        $saved = $this->method === 'GET' && $this->input($_GET, 'saved') === '1';
        $common = compact('message', 'submitted', 'saved');
        if ($section === 'topik') {
            $this->page('admin-topics', 'Kelola Topik', $common + ['items' => $store->topics()]);
        } elseif ($section === 'tantangan') {
            $this->page('admin-daily', 'Tantangan Harian', $common + $store->dailySchedule());
        } elseif ($section === 'jadwal') {
            if ($id === null) {
                $this->page('admin-exams', 'Kelola Jadwal Ujian', $common + ['items' => $store->examSchedule()]);
            } else {
                $examClasses = new ClassService(adminDatabase());
                $this->page('admin-exam', 'Editor Jadwal Ujian', $common + ['item' => $store->exam($id),
                    'classes' => $examClasses->list(), 'examClasses' => $examClasses->examClasses($id)]);
            }
        } elseif ($section === 'kuis') {
            $data = ['topics' => $store->topics(), 'levels' => AdminStore::LEVELS,
                'classes' => (new ClassService(adminDatabase()))->list()];
            if ($id === null) {
                $this->page('admin-quizzes', 'Kelola Kuis', $common + $data + ['items' => $store->quizzes()]);
            } else {
                $this->page('admin-quiz', 'Editor Kuis', $common + $data + ['item' => $store->quiz($id),
                    'quizClasses' => (new ClassService(adminDatabase()))->quizClasses($id)]);
            }
        } elseif ($section === 'siswa') {
            if ($id === null) {
                $this->page('admin-students', 'Kelola Siswa', $common + ['items' => $store->students()]);
            } else {
                $this->page('admin-student', 'Akun Siswa', $common + ['item' => $store->student($id)]);
            }
        } elseif ($section === 'guru') {
            $this->page('admin-teachers', 'Kelola Guru', $common + ['items' => $store->teachers()]);
        } else {
            $this->page('admin-history', 'Riwayat Admin', $store->history());
        }
    }

    private function classes(?int $id): void
    {
        $store = new ClassService(adminDatabase(), (int) $this->student['id']);
        if ($this->method === 'POST') {
            $action = $this->input($_POST, 'action');
            if ($id === null && $action === 'create') {
                $id = $store->create($this->input($_POST, 'name'), $this->input($_POST, 'year'));
            } elseif ($id !== null && in_array($action, ['assign', 'remove'], true)) {
                $student = $this->positive($this->input($_POST, 'student_id'));
                if ($action === 'assign') {
                    $store->assignStudent($id, $student);
                } else {
                    $store->removeStudent($id, $student);
                }
            } elseif ($id !== null && in_array($action, ['assign-teacher', 'remove-teacher'], true)) {
                $teacher = $this->positive($this->input($_POST, 'teacher_id'));
                if ($action === 'assign-teacher') {
                    $store->assignTeacher($id, $teacher);
                } else {
                    $store->removeTeacher($id, $teacher);
                }
            } elseif ($id !== null && in_array($action, ['archive', 'restore'], true)) {
                if ($action === 'archive' && $this->input($_POST, 'confirm') !== '1') {
                    throw new QuizProblem(422, 'Centang konfirmasi untuk mengarsipkan kelas.');
                }
                $store->setArchived($id, $action === 'archive');
            } else {
                throw new QuizProblem(422, 'Tindakan kelas tidak dikenali.');
            }
            $this->redirect("/admin/kelas/$id?saved=1");
            return;
        }
        if ($id === null) {
            $this->page('admin-classes', 'Kelola Kelas', ['items' => $store->list()]);
        } else {
            $this->page('admin-class', 'Anggota Kelas', $store->details($id));
        }
    }

    private function teacherQuiz(int $teacher, ?int $id): void
    {
        $editor = adminDatabase();
        $classes = new ClassService($editor);
        $store = new AdminStore($editor, $teacher);
        if ($id !== null && !$classes->teacherCanEdit($teacher, $id)) {
            throw new QuizProblem(404, 'Kuis tidak ditemukan.');
        }
        if ($this->method === 'POST') {
            $action = $this->input($_POST, 'action');
            if ($id === null && $action === 'create') {
                $id = $store->createQuiz($_POST, $this->positive($this->input($_POST, 'class_id')));
            } elseif ($id !== null && in_array($action, ['update', 'settings', 'draft', 'publish', 'question-save', 'question-delete'], true)) {
                $store->changeQuiz($id, $action, $_POST, true);
            } else {
                throw new QuizProblem(403, 'Tindakan ini tidak tersedia untuk guru.');
            }
            $this->redirect("/guru/kuis/$id?saved=1");
            return;
        }
        $data = ['topics' => $store->topics(), 'levels' => AdminStore::LEVELS,
            'classes' => $classes->teacherClasses($teacher), 'message' => '', 'submitted' => [], 'saved' => false];
        if ($id === null) {
            $this->page('teacher-quizzes', 'Kuis Guru', $data + ['items' => $classes->teacherQuizzes($teacher)]);
        } else {
            $editor->beginTransaction();
            try {
                $editor->query('SELECT pg_advisory_xact_lock_shared(476152004)');
                if (!$classes->teacherCanEdit($teacher, $id)) {
                    throw new QuizProblem(404, 'Kuis tidak ditemukan.');
                }
                $page = ['item' => $store->quiz($id), 'quizClasses' => $classes->quizClasses($id),
                    'teacherEditor' => true, 'teacherResults' => $classes->teacherResults($teacher, $id)];
                $editor->commit();
            } catch (Throwable $error) {
                if ($editor->inTransaction()) {
                    $editor->rollBack();
                }
                throw $error;
            }
            $this->page('admin-quiz', 'Editor Kuis', $data + $page);
        }
    }

    private function quests(int $actor, bool $teacher): void
    {
        $editor = adminDatabase();
        $service = new QuestService($editor);
        $base = $teacher ? '/guru/quest' : '/admin/quest';
        $message = '';
        $submitted = [];
        if ($this->method === 'POST') {
            try {
                $action = $this->input($_POST, 'action');
                if ($action === 'archive') {
                    $service->archive($actor, $this->positive($this->input($_POST, 'quest_id')));
                } elseif ($action === 'create') {
                    $service->create($actor, $_POST);
                } else {
                    throw new QuizProblem(422, 'Tindakan quest tidak tersedia.');
                }
                $this->redirect($base . '?saved=1');
                return;
            } catch (QuizProblem $problem) {
                if (!in_array($problem->status, [403, 422], true)) { throw $problem; }
                http_response_code($problem->status);
                $message = $problem->getMessage();
                $submitted = $_POST;
            }
        }
        $classes = new ClassService($editor);
        $quizStore = new AdminStore($editor, $actor);
        $query = $editor->prepare(<<<'SQL'
            SELECT DISTINCT q.id, q.title, q.starts_at, q.ends_at, q.target, q.xp, q.created_by, q.archived_at
            FROM eduquest.quests q
            WHERE NOT CAST(? AS boolean) OR EXISTS (
                SELECT 1 FROM eduquest.quest_classes qc
                JOIN eduquest.class_teachers ct ON ct.class_id = qc.class_id
                JOIN eduquest.classes c ON c.id = ct.class_id AND c.archived_at IS NULL
                WHERE qc.quest_id = q.id AND ct.teacher_id = ?
            ) ORDER BY q.starts_at DESC LIMIT 100
            SQL);
        $query->execute([$teacher ? 'true' : 'false', $actor]);
        $this->page('quest-admin', 'Kelola Quest', [
            'base' => $base, 'teacher' => $teacher, 'actor' => $actor, 'items' => $query->fetchAll(),
            'classes' => $teacher ? $classes->teacherClasses($actor) : $classes->list(),
            'quizzes' => $teacher ? $classes->teacherQuizzes($actor) : $quizStore->quizzes(),
            'message' => $message, 'submitted' => $submitted,
            'saved' => $this->method === 'GET' && $this->input($_GET, 'saved') === '1',
        ]);
    }

    private function masteryManage(int $actor, bool $teacher): void
    {
        $editor = adminDatabase();
        $service = new MasteryService($editor);
        $base = $teacher ? '/guru/mastery' : '/admin/mastery';
        $message = '';
        $submitted = [];
        if ($this->method === 'POST') {
            try {
                if ($this->input($_POST, 'action') !== 'save') { throw new QuizProblem(422, 'Tindakan tidak tersedia.'); }
                $service->save($actor, $_POST);
                $this->redirect($base . '?saved=1');
                return;
            } catch (QuizProblem $problem) {
                if (!in_array($problem->status, [403, 422], true)) { throw $problem; }
                http_response_code($problem->status);
                $message = $problem->getMessage();
                $submitted = $_POST;
            }
        }
        $classes = new ClassService($editor);
        $store = new AdminStore($editor, $actor);
        $this->page('mastery-admin', 'Kelola Mastery Tree', [
            'base' => $base, 'teacher' => $teacher, 'items' => $service->manage($actor, $teacher),
            'prerequisiteOptions' => $service->prerequisiteOptions($actor),
            'classes' => $teacher ? $classes->teacherClasses($actor) : $classes->list(),
            'quizzes' => $teacher ? $classes->teacherQuizzes($actor) : $store->quizzes(),
            'topics' => $store->topics(), 'message' => $message, 'submitted' => $submitted,
            'saved' => $this->method === 'GET' && $this->input($_GET, 'saved') === '1',
        ]);
    }

    private function community(int $actor, string $page): void
    {
        $store = new Community($this->db);
        $action = $this->input($_POST, 'action');
        if ($this->method === 'POST' && $page === '' && $action === 'create') {
            try {
                $id = $store->submit($actor, $_POST);
                $this->redirect("/komunitas/$id");
            } catch (QuizProblem $problem) {
                $this->communityForm($store, '/komunitas', 'create', $_POST, $problem);
            }
            return;
        }
        if ($this->method === 'GET' && $page === 'buat') {
            $values = [];
            $copy = $this->input($_GET, 'salin');
            if ($copy !== '') {
                $item = $store->question($this->positive($copy, 404), $actor);
                if (!$item['owns'] || $item['status'] !== 'rejected') {
                    throw new QuizProblem(404, 'Pengajuan untuk disalin tidak ditemukan.');
                }
                $values = $item + $item['options'];
            }
            $this->communityForm($store, '/komunitas', 'create', $values);
            return;
        }
        if ($this->method === 'GET' && $page === 'saya') {
            $number = $this->communityPage('hal');
            $rows = $store->submissions($actor, $number);
            $this->page('community-submissions', 'Pengajuan Saya', compact('number') + ['items' => array_slice($rows, 0, 20), 'hasNext' => count($rows) > 20]);
            return;
        }
        if ($this->method === 'GET' && $page === '') {
            $search = $this->input($_GET, 'q');
            if (!mb_check_encoding($search, 'UTF-8') || str_contains($search, "\0")) {
                throw new QuizProblem(422, 'Kata pencarian tidak valid.');
            }
            $topics = $store->topics();
            $topic = $this->input($_GET, 'topik');
            $level = $this->input($_GET, 'level');
            $order = $this->input($_GET, 'urut', 'baru');
            $filter = [
                'q' => mb_substr(trim($search), 0, 120),
                'topik' => in_array($topic, array_column($topics, 'slug'), true) ? $topic : '',
                'level' => isset(Community::LEVELS[$level]) ? $level : '',
                'urut' => isset(Community::ORDERS[$order]) ? $order : 'baru',
            ];
            $number = $this->communityPage('hal');
            $this->page('community', 'Bank Soal Komunitas', compact('topics', 'filter', 'number') + $store->catalog(
                $filter['q'], $filter['topik'], $filter['level'], $filter['urut'], $number,
            ));
            return;
        }
        if ($page === '' || !ctype_digit($page)) {
            throw new QuizProblem(404, 'Halaman komunitas tidak ditemukan.');
        }
        $id = $this->positive($page, 404);
        $endpoint = "/komunitas/$id";
        if ($this->method === 'POST') {
            try {
                $store->interact($id, $actor, $action, $_POST);
            } catch (QuizProblem $problem) {
                if ($action !== 'update' || $problem->status !== 422) {
                    throw $problem;
                }
                $this->communityForm($store, $endpoint, 'update', $_POST, $problem);
                return;
            }
            $this->redirect($action === 'delete' ? '/komunitas/saya' : $endpoint);
            return;
        }
        $item = $store->question($id, $actor, commentPage: $this->communityPage('komentar'));
        if ($this->input($_GET, 'edit') === '1' && $item['owns'] && $item['status'] === 'pending') {
            $this->communityForm($store, $endpoint, 'update', $item + $item['options']);
            return;
        }
        $this->page('community-question', $item['title'], compact('item', 'endpoint'));
    }

    private function communityForm(Community $store, string $endpoint, string $action, array $values, ?QuizProblem $problem = null): void
    {
        $title = $action === 'create' ? 'Bagikan Soal' : 'Ubah Pengajuan';
        $message = $problem?->getMessage() ?? '';
        if ($problem !== null) {
            http_response_code($problem->status);
        }
        $this->page('community-form', $title, compact('endpoint', 'action', 'values', 'message') + ['topics' => $store->topics()]);
    }

    private function communityModeration(int $actor, ?int $id): void
    {
        $store = new Community(adminDatabase());
        if ($id === null) {
            if ($this->method !== 'GET') {
                throw new QuizProblem(405, 'Pilih satu soal untuk ditinjau.');
            }
            $status = $this->input($_GET, 'status', 'pending');
            $status = isset(Community::STATES[$status]) ? $status : 'pending';
            $number = $this->communityPage('hal');
            $rows = $store->queue($status, $number);
            $this->page('community-moderation', 'Moderasi Komunitas', compact('status', 'number') + ['items' => array_slice($rows, 0, 20), 'hasNext' => count($rows) > 20]);
            return;
        }
        $endpoint = "/admin/komunitas/$id";
        if ($this->method === 'POST') {
            $store->moderate($id, $actor, $this->input($_POST, 'action'), $_POST);
            $this->redirect($endpoint);
            return;
        }
        $this->page('community-moderate', 'Tinjau Soal Komunitas', compact('endpoint') + ['item' => $store->question($id, $actor, true, $this->communityPage('komentar'))]);
    }

    private function communityPage(string $key): int
    {
        return min(10000, $this->positive($this->input($_GET, $key, '1')));
    }

    private function mentorStudent(int $student, string $segment): void
    {
        $store = new Mentor($this->db);
        $action = $this->input($_POST, 'action');
        if ($segment === 'sesi') {
            if ($this->method === 'POST') {
                if ($action !== 'cancel') {
                    throw new QuizProblem(422, 'Tindakan pemesanan tidak dikenali.');
                }
                $store->cancel($student, $_POST);
                $this->redirect('/mentor/sesi');
                return;
            }
            $number = $this->communityPage('hal');
            $this->page('mentor-sessions', 'Sesi Mentor Saya',
                $this->mentorPagination($store->studentSessions($student, $number), $number, '/mentor/sesi', 'Halaman sesi saya'));
            return;
        }
        if ($segment !== '') {
            $id = $this->positive($segment, 404);
            if ($this->method === 'POST') {
                if ($action !== 'book') {
                    throw new QuizProblem(422, 'Tindakan pemesanan tidak dikenali.');
                }
                $store->book($id, $student, $_POST);
                $this->redirect('/mentor/sesi');
                return;
            }
            $item = $store->profile($id);
            $number = $this->communityPage('hal');
            $this->page('mentor-profile', $item['name'], compact('item') +
                $this->mentorPagination($store->slots($id, $number), $number, "/mentor/$id", 'Halaman slot', 'slots'));
            return;
        }
        if ($this->method !== 'GET') {
            throw new QuizProblem(405, 'Pilih jadwal dari halaman mentor.');
        }
        $search = $this->input($_GET, 'q');
        if (!mb_check_encoding($search, 'UTF-8') || str_contains($search, "\0")) {
            throw new QuizProblem(422, 'Kata pencarian tidak valid.');
        }
        $category = $this->input($_GET, 'kategori');
        $price = $this->input($_GET, 'tarif');
        if ($price !== '' && filter_var($price, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000000]]) === false) {
            throw new QuizProblem(422, 'Masukkan batas tarif yang valid.');
        }
        $filters = [
            'q' => mb_substr(trim($search), 0, 120),
            'kategori' => isset(Mentor::CATEGORIES[$category]) ? $category : '',
            'tarif' => $price,
            'tersedia' => $this->input($_GET, 'tersedia') === '1',
        ];
        $items = $store->catalog($filters['q'], $filters['kategori'], $filters['tarif'], $filters['tersedia']);
        $this->page('mentor-directory', 'Cari Mentor', compact('filters', 'items'));
    }

    private function mentorAdmin(int $actor, string $segment, string $booking): void
    {
        if ($booking !== '' && $segment !== 'sesi') {
            throw new QuizProblem(404, 'Halaman mentor tidak ditemukan.');
        }
        $store = new Mentor(adminDatabase());
        $action = $this->input($_POST, 'action');
        if ($segment === 'sesi') {
            if ($booking === '') {
                if ($this->method !== 'GET') {
                    throw new QuizProblem(405, 'Pilih satu pemesanan untuk ditinjau.');
                }
                $number = $this->communityPage('hal');
                $this->page('mentor-admin-bookings', 'Pemesanan Mentor',
                    $this->mentorPagination($store->adminSessions($number), $number, '/admin/mentor/sesi', 'Halaman pemesanan'));
                return;
            }
            $id = $this->positive($booking, 404);
            if ($this->method === 'POST') {
                $store->decide($id, $actor, $action, $_POST);
                $this->redirect("/admin/mentor/sesi/$id");
                return;
            }
            $this->page('mentor-admin-booking', 'Kelola Pemesanan Mentor', ['item' => $store->session($id)]);
            return;
        }
        if ($segment !== '') {
            $id = $this->positive($segment, 404);
            if ($this->method === 'POST') {
                $store->manage($id, $actor, $action, $_POST);
                $this->redirect("/admin/mentor/$id");
                return;
            }
            $item = $store->profile($id, true);
            $number = $this->communityPage('hal');
            $this->page('mentor-admin-profile', 'Editor Mentor', compact('item') +
                $this->mentorPagination($store->slots($id, $number, true), $number, "/admin/mentor/$id", 'Halaman slot', 'slots'));
            return;
        }
        if ($this->method === 'POST') {
            if ($action !== 'create') {
                throw new QuizProblem(422, 'Tindakan mentor tidak dikenali.');
            }
            $id = $store->create($actor, $_POST);
            $this->redirect("/admin/mentor/$id");
            return;
        }
        $this->page('mentor-admin', 'Kelola Mentor', ['items' => $store->catalog('', '', '', false, true)]);
    }

    private function mentorPagination(array $rows, int $number, string $pagingPath, string $pagingLabel, string $key = 'items'): array
    {
        return compact('number', 'pagingPath', 'pagingLabel') + [
            $key => array_slice($rows, 0, Mentor::PAGE_SIZE), 'hasNext' => count($rows) > Mentor::PAGE_SIZE,
            'pagingKey' => 'hal', 'pagingFilters' => [],
        ];
    }

    private function adminMutation(AdminStore $store, string $section, ?int $id, string $action): string
    {
        if ($section === 'guru' && $id === null && $action === 'create') {
            $store->createTeacher($_POST);
            return '/admin/guru';
        }
        if ($section === 'guru' && $id !== null) {
            $store->changeTeacher($id, $action, $_POST);
            return '/admin/guru';
        }
        if ($section === 'jadwal') {
            if ($id === null) {
                if ($action !== 'create') {
                    throw new QuizProblem(422, 'Pilih satu jadwal untuk dikelola.');
                }
                return '/admin/jadwal/' . $store->createExam($_POST);
            }
            $store->changeExam($id, $action, $_POST);
            return $action === 'delete' ? '/admin/jadwal' : "/admin/jadwal/$id";
        }
        if ($section === 'tantangan') {
            $quiz = $action === 'save' ? $this->positive($this->input($_POST, 'quiz_id')) : null;
            $store->changeChallenge($action, $this->input($_POST, 'day'), $quiz);
            return '/admin/tantangan';
        }
        if ($section === 'topik') {
            $store->changeTopic($action, $_POST);
            return '/admin/topik';
        }
        if ($id === null && $action !== 'create') {
            throw new QuizProblem(422, 'Tindakan pembuatan tidak dikenali.');
        }
        if ($section === 'kuis') {
            if ($id === null) {
                return '/admin/kuis/' . $store->createQuiz($_POST);
            }
            $store->changeQuiz($id, $action, $_POST);
            return '/admin/kuis' . ($action === 'delete' ? '' : "/$id");
        }
        if ($id === null) {
            return '/admin/siswa/' . $store->createStudent($_POST);
        }
        $store->changeStudent($id, $action, $_POST);
        return '/admin/siswa' . ($action === 'delete' ? '' : "/$id");
    }

    private function readToken(string $key): ?string
    {
        $token = $this->input($_COOKIE, $this->cookieName($key));
        return preg_match('/^[a-f0-9]{64}$/D', $token) ? $token : null;
    }

    private function newCsrf(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->cookie(self::CSRF, $token, 0);
        return $token;
    }

    private function cookie(string $name, string $value, int $expires): void
    {
        setcookie($this->cookieName($name), $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $this->secureCookies(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    private function secureCookies(): bool
    {
        return getenv('EDUQUEST_SECURE_COOKIES') === '1' || in_array($_SERVER['HTTPS'] ?? '', ['on', '1'], true);
    }

    private function cookieName(string $name): string
    {
        return $this->secureCookies() ? '__Host-' . $name : $name;
    }

    private function page(string $view, string $title, array $data = []): void
    {
        $student = $this->student;
        $csrf = $this->csrf;
        $path = $this->path;
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/views/layout.php';
    }

    private function input(array $source, string $key, string $default = ''): string
    {
        return is_string($source[$key] ?? null) ? $source[$key] : $default;
    }

    private function positive(string $value, int $status = 422): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if ($number === false) {
            throw new QuizProblem($status, 'Nomor yang diminta tidak valid.');
        }
        return $number;
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location, true, 303);
    }

    private function respond(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
