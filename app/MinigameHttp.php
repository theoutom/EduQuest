<?php
declare(strict_types=1);

trait MinigameHttp
{
    private function minigameStudent(int $student): bool
    {
        $service = new MinigameService($this->db);
        if ($this->path === '/minigame' && $this->method === 'GET') {
            $this->page('minigame-list', 'Susun Langkah', ['items' => $service->catalog($student)]);
            return true;
        }
        if (preg_match('~^/minigame/hasil/(\d{1,10})$~D', $this->path, $match) && $this->method === 'GET') {
            $result = $service->result($student, $this->positive($match[1], 404));
            $this->page('minigame-result', 'Hasil permainan', compact('result'));
            return true;
        }
        if (!preg_match('~^/minigame/(\d{1,10})$~D', $this->path, $match)) { return false; }
        $id = $this->positive($match[1], 404);
        $message = '';
        $order = [];
        $submission = bin2hex(random_bytes(16));
        if ($this->method === 'POST') {
            $submission = $this->input($_POST, 'submission');
            $order = $_POST['order'] ?? [];
            if (!is_array($order)) { $order = []; }
            try {
                $result = $service->submit($student, $id, $submission, $order);
                $this->redirect('/minigame/hasil/' . $result['id']);
                return true;
            } catch (QuizProblem $problem) {
                if ($problem->status !== 422) { throw $problem; }
                http_response_code(422);
                $message = $problem->getMessage();
                if (!preg_match('/^[a-f0-9]{32}$/D', $submission)) { $submission = bin2hex(random_bytes(16)); }
            }
        }
        $game = $service->show($student, $id);
        $this->page('minigame-play', 'Susun Langkah', compact('game', 'message', 'order', 'submission'));
        return true;
    }

    private function minigameManage(int $actor, bool $teacher): void
    {
        $db = adminDatabase();
        $service = new MinigameService($db);
        $base = $teacher ? '/guru/minigame' : '/admin/minigame';
        $message = '';
        $submitted = [];
        if ($this->method === 'POST') {
            try {
                $action = $this->input($_POST, 'action');
                if ($action === 'create') {
                    $service->create($actor, $_POST);
                } elseif ($action === 'archive') {
                    $service->archive($actor, $this->positive($this->input($_POST, 'game_id')));
                } else {
                    throw new QuizProblem(422, 'Tindakan permainan tidak tersedia.');
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
        $classes = new ClassService($db);
        $this->page('minigame-admin', 'Kelola Susun Langkah', [
            'base' => $base, 'teacher' => $teacher, 'items' => $service->manage($actor, $teacher),
            'classes' => $teacher ? $classes->teacherClasses($actor) : $classes->list(),
            'message' => $message, 'submitted' => $submitted,
            'saved' => $this->method === 'GET' && $this->input($_GET, 'saved') === '1',
        ]);
    }
}
