import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

assert.ok(existsSync(fileURLToPath(new URL('../public/index.php', import.meta.url))), 'HTTP entry point is available');
import { Browser } from './http-client.mjs';

const alice = new Browser();
const bob = new Browser();
const location = response => response.headers.get('location');
assert.equal(location((await alice.visit('/kuis')).response), '/masuk');
assert.equal((await alice.visit('/masuk', { username: 'satrio', password: process.env.EDUQUEST_TEST_PASSWORD })).response.status, 403);
await alice.visit('/masuk');
assert.equal((await alice.visit('/masuk', { username: 'satrio', password: 'salah' })).response.status, 401);
await alice.login('satrio');
await bob.login('nadia');
const dashboard = async browser => {
  const { response, text } = await browser.visit('/');
  assert.equal(response.status, 200);
  assert.equal(response.headers.get('cache-control'), 'no-store');
  const activity = [...text.matchAll(/<time datetime="(\d{4}-\d{2}-\d{2})"[^>]*>[^<]+<\/time>\s*<span>(\d+) percobaan<\/span>/g)];
  assert.equal(activity.length, 7, 'Dashboard shows seven calendar days');
  const today = new Intl.DateTimeFormat('sv-SE', { timeZone: 'Asia/Jakarta', dateStyle: 'short' }).format(new Date());
  assert.equal(activity.at(-1)[1], today);
  return {
    completed: Number(text.match(/<dt>Kuis selesai<\/dt>\s*<dd>(\d+)<\/dd>/)?.[1]),
    score: text.match(/<dt>Rata-rata nilai<\/dt>\s*<dd>([^<]+)<\/dd>/)?.[1],
    today: Number(activity.at(-1)[2]),
  };
};
assert.match((await alice.visit('/')).text, /Halo, Satrio/);
const original = await dashboard(alice);
const peerOriginal = await dashboard(bob);
const filtered = await alice.visit('/kuis?q=struktur&topik=pemrograman&level=dasar');
assert.match(filtered.text, /Struktur Data &amp; Logika Pemrograman/);
assert.doesNotMatch(filtered.text, /SQL &amp; Basis Data Relasional/);
assert.match((await alice.visit('/kuis?q=zzzzzz')).text, /Kuis tidak ditemukan/);
const savedPath = '/kuis?status=tersimpan&q=http';
const bookmark = '/kuis/http-api/simpanan?status=tersimpan&q=http';
assert.ok((await alice.visit(savedPath)).text.includes('Kuis tidak ditemukan'));
assert.equal((await alice.visit(bookmark, { saved: '1', csrf: 'invalid' })).response.status, 403);
for (let retry = 0; retry < 2; retry++) {
  const added = await alice.visit(bookmark, { saved: '1' });
  assert.equal(added.response.status, 303);
  assert.equal(location(added.response), '/kuis?q=http&status=tersimpan');
}
assert.ok((await alice.visit(savedPath)).text.includes('Hapus simpanan'));
assert.ok((await bob.visit(savedPath)).text.includes('Kuis tidak ditemukan'));
assert.equal((await alice.visit(bookmark, { saved: '0' })).response.status, 303);
assert.ok((await alice.visit(savedPath)).text.includes('Kuis tidak ditemukan'));
assert.match((await alice.visit('/kuis/http-api')).text, /Mulai kuis/);
const { response: start } = await alice.visit('/kuis/http-api/mulai', {});
const session = location(start);
assert.match(session, /^\/sesi\/\d+$/);
assert.ok((await alice.visit('/kuis?status=aktif&q=http')).text.includes('Lanjutkan sesi'));
assert.deepEqual(await dashboard(alice), original);
const id = session.split('/').at(-1);
const exercise = await alice.visit(session);
assert.equal(exercise.response.status, 200);
assert.match(exercise.text, /Peta soal/);
assert.match(exercise.text, /Metode HTTP mana/);
assert.doesNotMatch(exercise.text, /GET meminta representasi/);
assert.equal((await alice.visit('/hasil/' + id)).response.status, 409);
assert.equal((await bob.visit(session)).response.status, 404);
assert.equal((await bob.visit(session + '/kumpulkan', {})).response.status, 404);
assert.equal((await alice.visit(session + '?soal=999')).response.status, 404);

const question = exercise.text.match(/name="question" value="(\d+)"/)?.[1];
const choices = [...exercise.text.matchAll(/name="option" value="(\d+)"/g)];
assert.ok(question && choices.length === 4, 'Question and option IDs come from rendered form');
const save = await alice.visit(session + '/jawaban', { question, option: choices[1][1], doubtful: '1', target: '2' });
assert.equal(location(save.response), session + '?soal=2');
assert.match((await alice.visit(session)).text, /name="doubtful" value="1" checked/);
const confirmation = await alice.visit(session + '/kumpulkan');
assert.match(confirmation.text, /2 soal belum dijawab/);
const nativeConfirm = await alice.visit(session + '/jawaban', { question, option: choices[1][1], doubtful: '1', target: 'kumpulkan', position: '2' });
assert.equal(location(nativeConfirm.response), session + '/kumpulkan?soal=2');
assert.match((await alice.visit(session + '/kumpulkan?soal=2')).text, /href="\/sesi\/\d+\?soal=2">Kembali ke soal/);
const { response: submit } = await alice.visit(session + '/kumpulkan', {});
assert.equal(location(submit), '/hasil/' + id);
assert.equal(location((await alice.visit(session + '/kumpulkan', {})).response), '/hasil/' + id);
const review = await alice.visit('/hasil/' + id);
assert.match(review.text, /GET meminta representasi/);
assert.match(review.text, /33/);
assert.doesNotMatch((await alice.visit('/hasil/' + id + '?status=kosong')).text, /GET meminta representasi/);
const printable = await alice.visit('/hasil/' + id + '?cetak=1&status=kosong');
assert.equal(printable.response.status, 200);
assert.ok(printable.text.includes('data-print-review') && printable.text.includes('GET meminta representasi'));
assert.ok(printable.text.includes('Ketepatan topik'));
assert.equal((await bob.visit('/hasil/' + id + '?cetak=1')).response.status, 404);
assert.ok((await alice.visit('/kuis?status=selesai&q=http')).text.includes('Sudah selesai'));
const progress = await dashboard(alice);
assert.equal(progress.completed, original.completed + 1);
assert.equal(progress.today, original.today + 1);
assert.match(progress.score, /^\d+\/100$/);
if (original.completed === 0) assert.equal(progress.score, '33/100');
assert.deepEqual(await dashboard(bob), peerOriginal);
assert.equal((await bob.visit('/hasil/' + id)).response.status, 404);
const personal = '/profil';
const profile = await alice.visit(personal);
assert.ok(profile.text.includes('Profil akademik') && profile.text.includes('Riwayat kuis'));
const details = { school: 'SMK Uji Profil', class_name: 'XI', program: 'PPLG', interests: 'Basis data', bio: '<foto> & belajar' };
assert.equal((await alice.visit(personal, { ...details, csrf: 'invalid' })).response.status, 403);
assert.equal((await alice.visit(personal, { ...details, school: 'x'.repeat(121) })).response.status, 422);
const savedProfile = await alice.visit(personal, { ...details, student_id: '2' });
assert.equal(savedProfile.response.status, 303);
assert.equal(location(savedProfile.response), '/profil?tersimpan=1');
const updated = await alice.visit(location(savedProfile.response));
assert.ok(updated.text.includes('Profil tersimpan') && updated.text.includes(details.school));
assert.ok(updated.text.includes('&lt;foto&gt; &amp; belajar'));
assert.ok(!(await bob.visit(personal)).text.includes(details.school));
assert.equal((await alice.visit(personal, { ...details, school: '', bio: '' })).response.status, 303);
assert.ok(!(await alice.visit(personal)).text.includes(details.school));
assert.match((await alice.visit('/komunitas')).text, /Bagikan soal/);
assert.match((await alice.visit('/mentor')).text, /Sesi saya/);
assert.equal((await alice.visit('/keluar', {})).response.status, 303);
assert.equal(location((await alice.visit('/')).response), '/masuk');
console.log('PASS: HTTP login, protection, dashboard, catalogue, quiz, review, private profile and logout');

