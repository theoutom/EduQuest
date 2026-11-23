<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';

$scope = $argv[1] ?? 'runtime';
if (!in_array($scope, ['runtime', 'editor'], true)) {
    exit(1);
}
$role = getenv($scope === 'editor' ? 'EDUQUEST_TEST_EDITOR_USER' : 'EDUQUEST_TEST_RUNTIME_USER') ?: '';
if (!preg_match('/^eduquest_[a-z0-9_]{1,40}$/D', $role)) {
    exit(1);
}
$db = database();
$grants = file_get_contents(dirname(__DIR__) . "/database/$scope-grants.sql");
$db->exec(str_replace('__RUNTIME_ROLE__', '"' . $role . '"', $grants));
