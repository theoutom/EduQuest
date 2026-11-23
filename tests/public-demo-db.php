<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';

$db = database();
$role = getenv('EDUQUEST_TEST_RUNTIME_USER') ?: $db->query('SELECT current_user')->fetchColumn();
if (!preg_match('/^eduquest(?:_[a-z0-9_]{1,40})?$/D', $role)) {
    exit(1);
}
$sql = file_get_contents(dirname(__DIR__) . '/database/public-demo.sql');
$db->exec(str_replace('__RUNTIME_ROLE__', '"' . $role . '"', $sql));
