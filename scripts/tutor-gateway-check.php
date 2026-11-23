<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/TutorGateway.php';

$mode = $argv[1] ?? '';
if (!in_array($mode, ['', '--live'], true) || count($argv) > 2) {
    fwrite(STDERR, "Usage: php scripts/tutor-gateway-check.php [--live]\n");
    exit(2);
}
$provider = getenv('EDUQUEST_TUTOR_PROVIDER') ?: '9router';
if (!in_array($provider, ['9router', 'vercel'], true)) {
    fwrite(STDERR, "Set EDUQUEST_TUTOR_PROVIDER to 9router or vercel for this check.\n");
    exit(2);
}
$gateway = TutorGateway::fromEnvironment($provider);
$status = $gateway->readiness();
if ($mode === '--live' && $status === 'ready') {
    $status = $gateway->choose('umum', 'mulai')['status'];
}
echo "Tutor Gateway ($provider): $status" . PHP_EOL;
exit(in_array($status, ['ready', 'ok'], true) ? 0 : 1);
