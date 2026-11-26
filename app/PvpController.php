<?php
declare(strict_types=1);

final class PvpController
{
    public function __construct(private PDO $db) {}

    public function run(int $student, ?int $id, string $method, array $post, callable $page, callable $redirect): void
    {
        $service = new PvpService($this->db);
        $message = '';
        if ($method === 'POST') {
            try {
                $action = is_string($post['action'] ?? null) ? $post['action'] : '';
                if ($id === null && $action === 'invite') {
                    $id = $service->invite($student, $this->number($post, 'opponent'), $this->number($post, 'class_id'), $this->number($post, 'quiz_id'));
                } elseif ($id !== null && $action === 'accept') {
                    $service->accept($student, $id);
                } elseif ($id !== null && $action === 'cancel') {
                    $service->cancel($student, $id);
                } elseif ($id !== null && $action === 'answer') {
                    $service->answer($student, $id, $this->number($post, 'question'), $this->number($post, 'option'));
                } elseif ($id !== null && $action === 'finish') {
                    $service->finish($student, $id);
                } else {
                    throw new QuizProblem(422, 'Tindakan pertandingan tidak tersedia.');
                }
                $redirect($id === null ? '/arena' : "/arena/$id");
                return;
            } catch (QuizProblem $error) {
                if (!in_array($error->status, [409, 422], true)) throw $error;
                http_response_code($error->status);
                $message = $error->getMessage();
            }
        }
        if ($id === null) {
            $page('pvp-lobby', 'Arena Kuis', ['lobby' => $service->lobby($student), 'message' => $message]);
            return;
        }
        $page('pvp-match', 'Pertandingan Kuis', ['match' => $service->view($student, $id), 'playerId' => $student, 'message' => $message]);
    }

    private function number(array $data, string $key): int
    {
        $value = filter_var($data[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if ($value === false) throw new QuizProblem(422, 'Pilih nilai yang valid.');
        return $value;
    }
}
