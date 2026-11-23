import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';

const request = (environment, path, publicDemo = '0') => execFileSync(process.env.EDUQUEST_PHP ?? 'php', [
  '-r', '$_SERVER["REQUEST_METHOD"] = "GET"; $_SERVER["REQUEST_URI"] = $argv[1]; '
  + 'require "public/index.php"; echo "\\nHTTP ", http_response_code();',
  path,
], {
  encoding: 'utf8',
  env: {
    ...process.env,
    VERCEL_ENV: environment,
    EDUQUEST_DEMO_MODE: '1',
    EDUQUEST_PUBLIC_DEMO: publicDemo,
    EDUQUEST_DB_DSN: 'pgsql:host=127.0.0.1;port=1;dbname=unavailable;connect_timeout=1',
  },
  stdio: ['ignore', 'pipe', 'ignore'],
});

assert.match(request('preview', '/masuk'), /Demo EduQuest[\s\S]*HTTP 503/);
assert.doesNotMatch(request('production', '/masuk'), /Demo EduQuest/);
assert.doesNotMatch(request('preview', '/admin'), /Demo EduQuest/);
assert.match(request('production', '/masuk', '1'), /Demo EduQuest[\s\S]*HTTP 503/);
console.log('PASS: demo landing only applies to the Preview entry points');
