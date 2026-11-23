<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/database.php';
require __DIR__ . '/postgres-verifier.php';

$role = $argv[1] ?? '';
$scope = $argv[2] ?? 'runtime';
$flag = $argv[3] ?? '';
$refresh = $flag === '--refresh-grants';
$password = $refresh ? '' : rtrim((string) fgets(STDIN, 258), "\r\n");
if (!in_array($flag, ['', '--refresh-grants'], true) || !in_array($scope, ['runtime', 'editor'], true)
    || !preg_match('/^eduquest_[a-z0-9_]{1,40}$/D', $role)
    || (!$refresh && (strlen($password) < 24 || strlen($password) > 128 || !preg_match('/^[!-~]+$/D', $password)))) {
    fwrite(STDERR, "Usage: php scripts/runtime-role.php <eduquest_role> [runtime|editor] [--refresh-grants]\n");
    fwrite(STDERR, "Creating a role requires a 24–128 character ASCII password via stdin.\n");
    exit(1);
}

try {
    $db = database();
    $db->beginTransaction();
    $lookup = $db->prepare(<<<'SQL'
        SELECT rolcanlogin AND NOT (rolsuper OR rolcreatedb OR rolcreaterole OR rolreplication OR rolbypassrls) AS restricted,
            has_column_privilege(oid, 'eduquest.questions', 'stem', 'UPDATE') AS editor,
            has_column_privilege(oid, 'eduquest.answers', 'option_id', 'UPDATE') AS runtime
        FROM pg_roles WHERE rolname = ?
        SQL);
    $lookup->execute([$role]);
    $current = $lookup->fetch();
    if (!$refresh && $current) {
        throw new RuntimeException('Choose a fresh role name. Existing roles may carry additional privileges.');
    }
    $identifier = '"' . $role . '"';
    if ($refresh) {
        if (!$current || !$current['restricted'] || !$current[$scope] || ($current['editor'] && $current['runtime'])) {
            throw new RuntimeException('Choose an existing dedicated role that matches the requested scope.');
        }
    } else {
        $verifier = postgresVerifier($password);
        unset($password);
        $db->exec("CREATE ROLE $identifier LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS PASSWORD " . $db->quote($verifier));
    }
    $grants = file_get_contents(dirname(__DIR__) . "/database/$scope-grants.sql");
    $db->exec(str_replace('__RUNTIME_ROLE__', $identifier, $grants));
    $db->commit();
    echo "Application database role configured.\n";
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, $error instanceof PDOException ? "Database role setup failed. Use the schema owner with role-management privileges.\n" : $error->getMessage() . PHP_EOL);
    exit(1);
}
