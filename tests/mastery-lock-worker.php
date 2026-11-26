<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/QuizService.php';
require dirname(__DIR__) . '/app/MasteryService.php';
$input = ['title' => 'Uji urutan lock', 'description' => 'Kompetensi pengujian', 'topic' => 'pemrograman',
    'quiz_id' => $argv[2], 'class_id' => $argv[3], 'prerequisites' => []];
(new MasteryService(database()))->save((int) $argv[1], $input);
