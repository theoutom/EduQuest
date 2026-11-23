import assert from 'node:assert/strict';
import { Browser } from './http-client.mjs';

const first = new Browser();
const later = new Browser();
assert.equal((await first.visit('/')).response.headers.get('location'), '/masuk');
const landing = await first.visit('/masuk');
assert.ok(landing.text.includes('Coba demo'));
assert.ok(!landing.text.includes('type="password"'));
assert.equal((await first.visit('/masuk', { csrf: 'wrong' })).response.status, 403);
for (const guest of [first, later]) {
  await guest.visit('/masuk');
  const admitted = await guest.visit('/masuk', {});
  assert.equal(admitted.response.status, 303);
  assert.ok(admitted.response.headers.getSetCookie().some(cookie => /eduquest_session=.*httponly/i.test(cookie)));
  assert.equal((await guest.visit('/admin')).response.status, 403);
  assert.equal((await guest.visit('/mentor')).response.status, 403);
}
await first.visit('/kuis');
const started = await first.visit('/kuis/http-api/mulai', {});
const session = started.response.headers.get('location');
assert.match(session, /^\/sesi\/\d+$/);
assert.equal((await later.visit(session)).response.status, 404);
assert.equal((await first.visit(session + '/tutor')).response.status, 403);
const submitted = await first.visit(session + '/kumpulkan', {});
assert.equal(submitted.response.status, 303);
assert.equal((await first.visit(submitted.response.headers.get('location'))).response.status, 200);
console.log('PASS: public guest sessions, isolation, submission and tour scope');
