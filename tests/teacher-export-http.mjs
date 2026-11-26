import assert from 'node:assert/strict';
import { Browser, base } from './http-client.mjs';

const admin = new Browser(), teacher = new Browser(), student = new Browser(), peer = new Browser();
await Promise.all([admin.login('operator'), teacher.login('guru-uji'), student.login('satrio'), peer.login('nadia')]);
await Promise.all([admin.visit('/admin/kelas'), teacher.visit('/guru'), student.visit('/'), peer.visit('/')]);
const created = await admin.visit('/admin/kelas', { action: 'create', name: 'Kelas Ekspor', year: '2026/2027' });
assert.equal(created.response.status, 303);
const classPath = created.response.headers.get('location').replace(/\?saved=1$/, '');
const classId = classPath.split('/').at(-1);
const page = await admin.visit(classPath);
const teacherId = page.text.match(/Guru Uji[\s\S]*?name="teacher_id" value="(\d+)"/)[1];
assert.equal((await admin.visit(classPath, { action: 'assign-teacher', teacher_id: teacherId })).response.status, 303);
assert.equal((await admin.visit(classPath, { action: 'assign', student_id: '1' })).response.status, 303);
const createdQuiz = await teacher.visit('/guru/kuis', { action: 'create', slug: 'ekspor-guru', title: 'Kuis Ekspor', summary: 'Latihan', topic: 'pemrograman', level: 'dasar', attempt_limit: '2', minutes: '5', passing: '60', class_id: classId });
assert.equal(createdQuiz.response.status, 303);
const quizPath = createdQuiz.response.headers.get('location').replace(/\?saved=1$/, '');
const draft = (await teacher.visit(quizPath)).text.match(/name="draft_id" value="(\d+)"/)[1];
assert.equal((await teacher.visit(quizPath, { action: 'question-save', draft_id: draft, stem: 'Berapakah satu tambah satu?', a: 'Dua', b: 'Tiga', correct: 'a', explanation: 'Hasilnya dua.' })).response.status, 303);
assert.equal((await teacher.visit(quizPath, { action: 'publish', draft_id: draft })).response.status, 303);
const start = await student.visit('/kuis/ekspor-guru/mulai', {});
assert.equal(start.response.status, 303);
const session = start.response.headers.get('location');
assert.equal((await student.visit(session + '/kumpulkan', {})).response.status, 303);
async function download(browser, path) {
  return fetch(new URL(path, base), { redirect: 'manual', headers: { cookie: [...browser.cookies].map(pair => pair.join('=')).join('; ') } });
}
for (const kind of ['nilai', 'soal']) for (const format of ['pdf', 'xlsx']) {
  const path = `${quizPath}/ekspor/${kind}/${format}`;
  const response = await download(teacher, path);
  assert.equal(response.status, 200);
  assert.match(response.headers.get('content-disposition'), /attachment/);
  const body = Buffer.from(await response.arrayBuffer());
  assert.ok(body.subarray(0, format === 'pdf' ? 5 : 4).equals(Buffer.from(format === 'pdf' ? '%PDF-' : 'PK\x03\x04', 'binary')));
  assert.ok([403, 404].includes((await download(peer, path)).status));
}
assert.equal((await admin.visit(classPath, { action: 'remove-teacher', teacher_id: teacherId })).response.status, 303);
assert.equal((await download(teacher, `${quizPath}/ekspor/nilai/pdf`)).status, 404);
console.log('PASS: teacher exports real PDF and XLSX; other roles and revoked teachers are denied.');
