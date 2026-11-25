import { randomBytes } from 'node:crypto';
import { execFileSync, spawn } from 'node:child_process';
import { once } from 'node:events';
import { existsSync } from 'node:fs';
import { createServer as createHttpServer } from 'node:http';
import { createServer } from 'node:net';
import path from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';

const php = process.env.EDUQUEST_PHP ?? 'php';
const suites = process.argv.slice(2);
const upgrade = suites.includes('upgrade');
if (upgrade && suites.length !== 1) throw new Error('Run the upgrade suite separately.');
const enabled = suite => suites.length === 0 || suites.includes(suite);
const publicDemo = suites.includes('public-demo');
const phpFlags = [];
const evaluate = (code, options = {}) => execFileSync(php, [...phpFlags, '-r', code], { encoding: 'utf8', ...options }).trim();
if (process.platform === 'win32' && evaluate('echo (int) extension_loaded("pdo_pgsql");') !== '1') {
  const extensions = path.join(path.dirname(evaluate('echo PHP_BINARY;')), 'ext');
  if (existsSync(path.join(extensions, 'php_pdo_pgsql.dll'))) {
    phpFlags.push('-d', 'extension_dir=' + extensions, '-d', 'extension=pdo_pgsql');
  }
}
if (evaluate('echo (int) extension_loaded("pdo_pgsql");') !== '1') {
  throw new Error('Enable the PDO_PGSQL extension before running the tests.');
}

const socket = createServer();
socket.listen(0, '127.0.0.1');
await once(socket, 'listening');
const { port } = socket.address();
await new Promise(resolve => socket.close(resolve));

const name = 'eduquest_test_' + randomBytes(8).toString('hex');
const command = async (file, args, env, input) => {
  const child = spawn(file, args, { env, stdio: [input ? 'pipe' : 'ignore', 'inherit', 'inherit'], windowsHide: true });
  if (input) child.stdin.end(input);
  const [status] = await once(child, 'exit');
  if (status !== 0) throw new Error(file + ' ' + args.at(-1) + ' failed');
};
const dsn = execFileSync(php, [...phpFlags, 'tests/database.php', 'create', name], { encoding: 'utf8' }).trim();
const url = 'http://127.0.0.1:' + port;
const env = { ...process.env, EDUQUEST_DB_DSN: dsn, EDUQUEST_TEST_URL: url, EDUQUEST_SECURE_COOKIES: '0' };
const password = randomBytes(24).toString('hex');
const unconfiguredGateway = process.env.EDUQUEST_TEST_GATEWAY_UNCONFIGURED === '1';
const use9Router = process.env.EDUQUEST_TEST_9ROUTER === '1' && !unconfiguredGateway;
const offlineTutor = unconfiguredGateway || process.env.EDUQUEST_TEST_TUTOR_FALLBACK === '1'
  || evaluate('echo (int) extension_loaded("curl");') !== '1';
const testEnv = { ...env, EDUQUEST_TEST_PASSWORD: password, EDUQUEST_TEST_TUTOR_FALLBACK: offlineTutor ? '1' : '0',
  EDUQUEST_TEST_9ROUTER: use9Router ? '1' : '0' };
let server;
let modelFixture;
let modelViolation = false;
let log = '';
try {
  if (enabled('demo')) await command(process.execPath, ['tests/demo-landing.mjs'], env);
  if (enabled('tutor')) await command(php, [...phpFlags, 'tests/tutor-gateway.php'], env);
  if (upgrade) await command(php, [...phpFlags, 'tests/upgrade.php', 'prepare'], env);
  await command(php, [...phpFlags, 'scripts/migrate.php'], env);
  if (suites.includes('classes')) await command(php, [...phpFlags, 'tests/classes.php'], env);
  if (upgrade) {
    await command(php, [...phpFlags, 'scripts/migrate.php'], env);
    await command(php, [...phpFlags, 'tests/upgrade.php', 'verify'], env);
  }
  if (enabled('quiz')) await command(php, [...phpFlags, 'tests/quiz.php'], env);
  if (publicDemo || enabled('http') || enabled('browser') || enabled('auth') || enabled('crud') || enabled('community') || enabled('tutor') || enabled('mentor') || suites.includes('classes-http')) {
    for (const [username, role, label] of [['satrio', 'student', 'Satrio'], ['nadia', 'student', 'Nadia'], ['operator', 'admin', 'Operator']]) {
      await command(php, [...phpFlags, 'scripts/account.php', 'set', username, role, label], env, password + '\n');
    }
    if (suites.includes('classes-http')) {
      await command(php, [...phpFlags, 'scripts/account.php', 'set', 'guru-uji', 'teacher', 'Guru Uji'], env, password + '\n');
    }
    if (enabled('auth')) await command(php, [...phpFlags, 'tests/auth-state.php'], testEnv);
    const webEnv = { ...env };
    if (process.env.EDUQUEST_TEST_RUNTIME_USER) {
      await command(php, [...phpFlags, 'tests/grant-runtime.php'], env);
      webEnv.EDUQUEST_DB_USER = process.env.EDUQUEST_TEST_RUNTIME_USER;
      webEnv.EDUQUEST_DB_PASSWORD = process.env.EDUQUEST_TEST_RUNTIME_PASSWORD ?? '';
      await command(php, [...phpFlags, 'tests/privileges.php'], webEnv);
    }
    if (process.env.EDUQUEST_TEST_EDITOR_USER) {
      await command(php, [...phpFlags, 'tests/grant-runtime.php', 'editor'], env);
    }
    if (publicDemo) {
      await command(php, [...phpFlags, 'tests/public-demo-db.php'], env);
      webEnv.EDUQUEST_PUBLIC_DEMO = '1';
    }
    webEnv.EDUQUEST_ADMIN_DB_DSN = dsn;
    webEnv.EDUQUEST_ADMIN_DB_USER = process.env.EDUQUEST_TEST_EDITOR_USER ?? process.env.EDUQUEST_DB_USER ?? 'eduquest';
    webEnv.EDUQUEST_ADMIN_DB_PASSWORD = process.env.EDUQUEST_TEST_EDITOR_PASSWORD ?? process.env.EDUQUEST_DB_PASSWORD ?? '';
    if (process.env.EDUQUEST_TEST_EDITOR_USER) {
      await command(php, [...phpFlags, 'tests/editor-privileges.php'], webEnv);
    }
    delete webEnv.EDUQUEST_TEST_RUNTIME_PASSWORD;
    delete webEnv.EDUQUEST_TEST_EDITOR_PASSWORD;
    delete webEnv.EDUQUEST_TUTOR_MODEL;
    delete webEnv.EDUQUEST_TUTOR_OLLAMA_PORT;
    delete webEnv.AI_GATEWAY_API_KEY;
    delete webEnv.VERCEL_OIDC_TOKEN;
    delete webEnv.EDUQUEST_9ROUTER_API_KEY;
    delete webEnv.EDUQUEST_TUTOR_9ROUTER_MODEL;
    delete webEnv.EDUQUEST_TUTOR_9ROUTER_PORT;
    webEnv.EDUQUEST_TUTOR_PROVIDER = unconfiguredGateway ? 'vercel' : (use9Router ? '9router' : 'ollama');
    if (enabled('tutor') && !offlineTutor) {
      modelFixture = createHttpServer(async (request, response) => {
        let raw = '';
        for await (const chunk of request) raw += chunk;
        let body;
        try { body = JSON.parse(raw); } catch { body = {}; }
        if (use9Router) {
          const prompt = body.messages?.[0]?.content;
          if (request.url !== '/v1/chat/completions' || request.method !== 'POST'
            || request.headers.authorization !== 'Bearer fixture-local-key' || body.model !== 'fixture-combo'
            || body.messages?.length !== 1 || body.stream !== false || body.max_tokens !== 96
            || !/^Choose a Socratic follow-up for concept (modulo|sql|web|struktur|algoritma|umum) and help request (mulai|langkah|contoh)\. Choose 0 for first action, 1 for condition check, or 2 for a small example\. Return only a JSON object with the integer choice\.$/.test(prompt)) {
            modelViolation = true;
            response.writeHead(422).end();
            return;
          }
          const choice = prompt.includes('request contoh') ? 'PRIVATEOUTPUT' : 2;
          response.writeHead(200, { 'Content-Type': 'application/json' })
            .end(JSON.stringify({ choices: [{ finish_reason: 'stop', message: { content: JSON.stringify({ choice }) } }] }));
          return;
        }
        if (request.url !== '/api/generate' || request.method !== 'POST' || body.model !== 'fixture'
          || body.stream !== false || body.format !== 'json'
          || !/^Select a Socratic follow-up for concept (modulo|sql|web|struktur|algoritma|umum) and help request (mulai|langkah|contoh)\. Return JSON with one integer choice: 0 for a first action, 1 for a condition check, or 2 for a small example\.$/.test(body.prompt)) {
          modelViolation = true;
          response.writeHead(422).end();
          return;
        }
        const choice = body.prompt.includes('request contoh') ? 'PRIVATEOUTPUT' : 2;
        response.writeHead(200, { 'Content-Type': 'application/json' })
          .end(JSON.stringify({ response: JSON.stringify({ choice }) }));
      });
      modelFixture.listen(0, '127.0.0.1');
      await once(modelFixture, 'listening');
      if (use9Router) {
        webEnv.EDUQUEST_TUTOR_9ROUTER_MODEL = 'fixture-combo';
        webEnv.EDUQUEST_TUTOR_9ROUTER_PORT = String(modelFixture.address().port);
        webEnv.EDUQUEST_9ROUTER_API_KEY = 'fixture-local-key';
      } else {
        webEnv.EDUQUEST_TUTOR_MODEL = 'fixture';
        webEnv.EDUQUEST_TUTOR_OLLAMA_PORT = String(modelFixture.address().port);
      }
    }
    server = spawn(php, [...phpFlags, '-S', '127.0.0.1:' + port, '-t', 'public', 'router.php'], {
      env: webEnv,
      stdio: ['ignore', 'ignore', 'pipe'],
      windowsHide: true,
    });
    server.stderr.on('data', part => { log += part.toString(); });
    let ready = false;
    for (let retry = 0; retry < 60 && !ready; retry++) {
      ready = await fetch(url + '/masuk').then(response => response.ok, () => false);
      if (!ready) await delay(100);
    }
    if (!ready) throw new Error('PHP did not start on ' + url);
    if (publicDemo) await command(process.execPath, ['tests/public-demo.mjs'], testEnv);
    if (suites.includes('classes-http')) await command(process.execPath, ['tests/classes-http.mjs'], testEnv);
    if (enabled('http')) await command(process.execPath, ['tests/http.mjs'], testEnv);
    if (enabled('browser')) await command(process.execPath, ['tests/browser.mjs'], testEnv);
    if (enabled('crud')) await command(process.execPath, ['tests/admin-crud.mjs'], testEnv);
    if (enabled('community')) await command(process.execPath, ['tests/community.mjs'], testEnv);
    if (enabled('tutor')) await command(process.execPath, ['tests/tutor.mjs'], testEnv);
    if (enabled('mentor')) await command(process.execPath, ['tests/mentor.mjs'], testEnv);
    if (enabled('auth')) await command(process.execPath, ['tests/auth.mjs'], testEnv);
    if (modelViolation) throw new Error('Tutor sent data outside the closed model contract.');
  }
} catch (error) {
  if (log) console.error(log);
  throw error;
} finally {
  if (server && server.exitCode === null) {
    const exited = once(server, 'exit');
    server.kill();
    await exited;
  }
  if (modelFixture) await new Promise(resolve => modelFixture.close(resolve));
  await command(php, [...phpFlags, 'tests/database.php', 'drop', name], process.env);
}
