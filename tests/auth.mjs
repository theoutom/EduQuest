import assert from 'node:assert/strict';
import { Browser, base } from './http-client.mjs';

const password = process.env.EDUQUEST_TEST_PASSWORD;
const guest = new Browser();
assert.equal((await guest.visit('/admin')).response.headers.get('location'), '/masuk');
const login = await guest.visit('/masuk');
assert.doesNotMatch(login.text, /EduQuestDemo2026!|\bsatrio\b|\bnadia\b|password_hash/);
assert.equal(login.text.includes(password), false);
assert.equal(login.response.headers.get('cache-control'), 'no-store');
for (const path of ['/database/002_demo.sql', '/scripts/account.php', '/.env', '/app/database.php']) {
  assert.doesNotMatch((await guest.visit(path)).text, /INSERT INTO eduquest|EDUQUEST_DB_PASSWORD|<\?php/);
}
const crossSite = await guest.visit('/masuk', { username: 'satrio', password }, { 'sec-fetch-site': 'cross-site' });
assert.equal(crossSite.response.status, 403);

const learner = new Browser();
await learner.login('satrio');
assert.equal((await learner.visit('/admin', undefined, { 'x-role': 'admin' })).response.status, 403);
assert.doesNotMatch((await learner.visit('/')).text, /href="\/admin"/);

const admin = new Browser();
await admin.visit('/masuk');
const signedIn = await admin.visit('/masuk', { username: 'operator', password });
assert.equal(signedIn.response.headers.get('location'), '/admin');
assert.ok(signedIn.response.headers.getSetCookie().some(cookie => /eduquest_session=.*httponly.*samesite=strict/i.test(cookie)));
const page = await admin.visit('/admin');
assert.equal(page.response.status, 200);
assert.match(page.text, /Administrasi EduQuest/);
assert.doesNotMatch(page.text, /password_hash|token_hash|postgresql:\/\//);
assert.equal((await admin.visit('/kuis')).response.status, 403);
assert.equal((await admin.visit('/admin', {})).response.status, 405);
const oldSession = admin.cookies.get('eduquest_session');
assert.equal((await admin.visit('/keluar', {})).response.status, 303);
admin.cookies.set('eduquest_session', oldSession);
assert.equal((await admin.visit('/admin')).response.headers.get('location'), '/masuk');

const limited = new Browser();
await limited.visit('/masuk');
for (let i = 0; i < 5; i++) {
  assert.equal((await limited.visit('/masuk', { username: 'NADIA', password: 'invalid' })).response.status, 401);
}
const blocked = await limited.visit('/masuk', { username: 'nadia', password }, { 'x-forwarded-for': '192.0.2.23' });
assert.equal(blocked.response.status, 429);
assert.ok(Number(blocked.response.headers.get('retry-after')) > 0);
assert.equal((await fetch(base + '/assets/app.js')).status, 200);
console.log('PASS: credentials stay out of responses, admin role check, logout and login throttling');
