<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (getenv('EDUQUEST_SECURE_COOKIES') === '1' || in_array($_SERVER['HTTPS'] ?? '', ['on', '1'], true)) {
    header('Strict-Transport-Security: max-age=31536000');
}
header("Content-Security-Policy: default-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'; object-src 'none'");

$root = dirname(__DIR__);
require "$root/app/database.php";
require "$root/app/QuizService.php";
require "$root/app/Auth.php";
require "$root/app/AdminStore.php";
require "$root/app/Community.php";
require "$root/app/Tutor.php";
require "$root/app/TutorGateway.php";
require "$root/app/Mentor.php";
require "$root/app/StudentProfile.php";
require "$root/app/Http.php";
require "$root/views/helpers.php";

try {
    $app = new Http(database());
    $app->run();
} catch (QuizProblem $problem) {
    $app->error($problem);
} catch (Throwable $error) {
    $code = (string) $error->getCode();
    error_log('EduQuest request failed: ' . $error::class . (preg_match('/^[A-Z0-9]{5}$/D', $code) ? " ($code)" : ''));
    $problem = new QuizProblem(503, 'EduQuest belum dapat memproses permintaan. Coba lagi sebentar.');
    if (demoPreview() && $_SERVER['REQUEST_METHOD'] === 'GET'
        && in_array(explode('?', $_SERVER['REQUEST_URI'], 2)[0], ['/', '/masuk'], true)) {
        http_response_code(503);
        $title = 'Demo EduQuest';
        $view = 'demo-unavailable';
        $student = null;
        require "$root/views/layout.php";
    } elseif (isset($app)) {
        $app->error($problem);
    } else {
        http_response_code($problem->status);
        $title = 'EduQuest tidak tersedia';
        $message = $problem->getMessage();
        $view = 'error';
        $student = null;
        require "$root/views/layout.php";
    }
}
