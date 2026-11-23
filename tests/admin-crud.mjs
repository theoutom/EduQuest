import assert from 'node:assert/strict';
import { Browser } from './http-client.mjs';

const administrator = new Browser();
await administrator.login('operator');
const learner = new Browser();
await learner.login('satrio');
assert.equal((await learner.visit('/admin/topik')).response.status, 403);
assert.equal((await administrator.visit('/admin/topik')).response.status, 200, 'Admin can list and create topics');

function redirect(result, target) {
  assert.equal(result.response.status, 303);
  const path = result.response.headers.get('location').replace(/\?saved=1$/, '');
  assert.match(path, target);
  return path;
}

assert.equal((await administrator.visit('/admin/topik', { action: 'create', slug: 'algoritma', name: 'Algoritma', csrf: '' })).response.status, 403);
redirect(await administrator.visit('/admin/topik', { action: 'create', slug: 'algoritma', name: 'Algoritma' }), /^\/admin\/topik/);
redirect(await administrator.visit('/admin/topik', { action: 'update', slug: 'algoritma', name: 'Algoritma lanjut' }), /^\/admin\/topik/);
redirect(await administrator.visit('/admin/topik', { action: 'create', slug: 'sementara', name: 'Topik <sementara>' }), /^\/admin\/topik/);
assert.match((await administrator.visit('/admin/topik')).text, /Topik &lt;sementara&gt;/);
assert.equal((await administrator.visit('/admin/topik', { action: 'delete', slug: 'sementara' })).response.status, 422);
redirect(await administrator.visit('/admin/topik', { action: 'delete', slug: 'sementara', confirm: '1' }), /^\/admin\/topik/);
const quiz = redirect(await administrator.visit('/admin/kuis', {
  action: 'create', slug: 'kuis-admin', title: 'Kuis admin', summary: 'Soal dari panel admin.', topic: 'algoritma',
  level: 'dasar', attempt_limit: '3', minutes: '5', passing: '60',
}), /^\/admin\/kuis\/\d+$/);
assert.doesNotMatch((await learner.visit('/kuis')).text, /Kuis admin/);
const draftId = async () => (await administrator.visit(quiz)).text.match(/name="draft_id" value="(\d+)"/)[1];
const firstDraft = await draftId();
assert.equal((await administrator.visit(quiz, { action: 'publish', draft_id: firstDraft })).response.status, 422);
const fields = { action: 'question-save', stem: 'Berapa nilai dua tambah dua?', snippet: '  nilai = 2 + 2\n', explanation: 'Dua ditambah dua sama dengan empat.', a: '4', b: '5', correct: 'a' };
redirect(await administrator.visit(quiz, { ...fields, draft_id: firstDraft }), /^\/admin\/kuis\/\d+/);
redirect(await administrator.visit(quiz, { ...fields, draft_id: firstDraft, stem: 'Soal sementara untuk dihapus.' }), /^\/admin\/kuis\/\d+/);
const draftRows = [...new Set([...(await administrator.visit(quiz)).text.matchAll(/name="question_id" value="(\d+)"/g)].map(match => match[1]))];
assert.equal(draftRows.length, 2);
redirect(await administrator.visit(quiz, { action: 'question-delete', draft_id: firstDraft, question_id: draftRows[1], confirm: '1' }), /^\/admin\/kuis\/\d+/);
assert.doesNotMatch((await administrator.visit(quiz)).text, /Soal sementara untuk dihapus/);
redirect(await administrator.visit(quiz, { action: 'publish', draft_id: firstDraft }), /^\/admin\/kuis\/\d+/);
const opened = redirect(await learner.visit('/kuis/kuis-admin/mulai', {}), /^\/sesi\/\d+$/);
const exercise = await learner.visit(opened);
assert.match(exercise.text, /Berapa nilai dua tambah dua/);
assert.match(exercise.text, /<code>  nilai = 2 \+ 2\n<\/code>/);
assert.doesNotMatch(exercise.text, /Dua ditambah dua sama dengan empat/);
const question = exercise.text.match(/name="question" value="(\d+)"/)[1];
const option = exercise.text.match(/name="option" value="(\d+)"/)[1];
redirect(await learner.visit(opened + '/jawaban', { question, option, target: '1' }), /soal=1$/);
const result = redirect(await learner.visit(opened + '/kumpulkan', {}), /^\/hasil\//);
assert.match((await learner.visit(result)).text, /Dua ditambah dua sama dengan empat/);
const stillV1 = redirect(await learner.visit('/kuis/kuis-admin/mulai', {}), /^\/sesi\/\d+$/);

redirect(await administrator.visit(quiz, { action: 'draft' }), /^\/admin\/kuis\/\d+/);
const draft = await administrator.visit(quiz);
const nextDraft = await draftId();
const draftQuestion = draft.text.match(/name="question_id" value="(\d+)"/)[1];
assert.notEqual(draftQuestion, question);
assert.equal((await administrator.visit(quiz, { ...fields, draft_id: nextDraft, question_id: question })).response.status, 404);
assert.equal((await administrator.visit(quiz, { action: 'settings', draft_id: firstDraft, minutes: '7', passing: '75' })).response.status, 409, 'A stale form cannot overwrite a newer draft');
redirect(await administrator.visit(quiz, { ...fields, draft_id: nextDraft, question_id: draftQuestion, stem: 'Pertanyaan versi baru', explanation: 'Revisi baru untuk soal ini.' }), /^\/admin\/kuis\/\d+/);
redirect(await administrator.visit(quiz, { action: 'settings', draft_id: nextDraft, minutes: '6', passing: '75' }), /^\/admin\/kuis\/\d+/);
redirect(await administrator.visit(quiz, { action: 'publish', draft_id: nextDraft }), /^\/admin\/kuis\/\d+/);
assert.match((await learner.visit(result)).text, /Dua ditambah dua sama dengan empat/);
assert.match((await learner.visit('/kuis/kuis-admin')).text, /6 menit/);
assert.equal((await administrator.visit(quiz, { action: 'delete', confirm: '1' })).response.status, 409);
assert.equal((await administrator.visit('/admin/topik', { action: 'delete', slug: 'algoritma', confirm: '1' })).response.status, 409);
redirect(await administrator.visit(quiz, { action: 'archive', confirm: '1' }), /^\/admin\/kuis/);
assert.doesNotMatch((await learner.visit('/kuis')).text, /Kuis admin/);
assert.ok((await learner.visit('/')).text.includes('href="' + stillV1 + '"'));
const archivedOverview = await learner.visit('/kuis/kuis-admin');
assert.equal(archivedOverview.response.status, 200, 'Archived quiz keeps a working overview for a student with history');
assert.match(archivedOverview.text, /Kuis diarsipkan/);
assert.doesNotMatch(archivedOverview.text, /action="\/kuis\/kuis-admin\/mulai"/);
assert.match((await learner.visit(stillV1)).text, /Berapa nilai dua tambah dua/);
const oldResult = redirect(await learner.visit(stillV1 + '/kumpulkan', {}), /^\/hasil\//);
assert.match((await learner.visit(oldResult)).text, /Dua ditambah dua sama dengan empat/);
redirect(await administrator.visit(quiz, { action: 'restore' }), /^\/admin\/kuis/);
const second = redirect(await learner.visit('/kuis/kuis-admin/mulai', {}), /^\/sesi\/\d+$/);
assert.match((await learner.visit(second)).text, /Pertanyaan versi baru/);
const revised = redirect(await learner.visit(second + '/kumpulkan', {}), /^\/hasil\//);
assert.match((await learner.visit(revised)).text, /Revisi baru untuk soal ini/);
assert.match((await learner.visit(result)).text, /Dua ditambah dua sama dengan empat/);
assert.match((await learner.visit('/kuis')).text, /Kuis admin/);

const password = process.env.EDUQUEST_TEST_PASSWORD;
const student = redirect(await administrator.visit('/admin/siswa', { action: 'create', username: 'siswa-baru', name: 'Siswa Baru', password, role: 'admin' }), /^\/admin\/siswa\/\d+$/);
const other = new Browser();
await other.login('siswa-baru');
assert.equal((await other.visit('/admin')).response.status, 403);
assert.equal((await administrator.visit(student)).text.includes(password), false);
redirect(await administrator.visit(student, { action: 'update', name: 'Nama diperbarui' }), /^\/admin\/siswa/);
assert.match((await other.visit('/')).text, /Nama diperbarui/);
redirect(await administrator.visit(student, { action: 'disable', confirm: '1' }), /^\/admin\/siswa/);
assert.equal((await other.visit('/')).response.headers.get('location'), '/masuk');
redirect(await administrator.visit(student, { action: 'reset', password }), /^\/admin\/siswa/);
await other.login('siswa-baru');
redirect(await administrator.visit(student, { action: 'delete', confirm: '1' }), /^\/admin\/siswa$/);
assert.equal((await administrator.visit('/admin/siswa/3', { action: 'reset', password })).response.status, 404);
assert.equal((await administrator.visit('/admin/siswa/1', { action: 'delete', confirm: '1' })).response.status, 409);
assert.match((await administrator.visit('/admin/riwayat')).text, /Kuis admin/);
console.log('PASS: admin topic, quiz version, question, student and attempt workflow');
