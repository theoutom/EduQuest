import assert from 'node:assert/strict';
import { Browser } from './http-client.mjs';

const admin = new Browser();
const student = new Browser();
const teacher = new Browser();
await Promise.all([admin.login('operator'), student.login('satrio'), teacher.login('guru-uji')]);
assert.equal((await admin.visit('/admin/guru')).response.status, 200);
const teacherCreated = await admin.visit('/admin/guru', {
  action: 'create', username: 'guru-baru', name: 'Guru Baru', password: process.env.EDUQUEST_TEST_PASSWORD,
});
assert.equal(teacherCreated.response.status, 303);
const teachersPage = await admin.visit('/admin/guru');
assert.match(teachersPage.text, /Guru Baru/);
const newTeacherId = teachersPage.text.match(/Guru Baru[\s\S]*?action="\/admin\/guru\/(\d+)"/)[1];
const newTeacher = new Browser();
await newTeacher.login('guru-baru');
assert.equal((await newTeacher.visit('/guru')).response.status, 200);
const replacementPassword = 'kata-sandi-uji-guru-baru-2026';
assert.equal((await admin.visit('/admin/guru/' + newTeacherId, { action: 'reset', password: replacementPassword })).response.status, 303);
assert.equal((await newTeacher.visit('/guru')).response.headers.get('location'), '/masuk');
const loginAfterReset = new Browser();
await loginAfterReset.visit('/masuk');
assert.equal((await loginAfterReset.visit('/masuk', { username: 'guru-baru', password: process.env.EDUQUEST_TEST_PASSWORD })).response.status, 401);
assert.equal((await loginAfterReset.visit('/masuk', { username: 'guru-baru', password: replacementPassword })).response.status, 303);
assert.equal((await loginAfterReset.visit('/guru')).response.status, 200);
assert.equal((await student.visit('/admin/kelas')).response.status, 403);
assert.equal((await admin.visit('/admin/kelas')).response.status, 200);
const created = await admin.visit('/admin/kelas', { action: 'create', name: 'XI-PPLG-B', year: '2026/2027' });
assert.equal(created.response.status, 303);
const classPath = created.response.headers.get('location').replace(/\?saved=1$/, '');
assert.match(classPath, /^\/admin\/kelas\/\d+$/);
assert.equal((await admin.visit(classPath)).response.status, 200);
assert.equal((await teacher.visit('/admin/kelas')).response.status, 403);
assert.equal((await teacher.visit('/guru')).response.status, 200);
assert.doesNotMatch((await teacher.visit('/guru')).text, /XI-PPLG-B/);
const page = await admin.visit(classPath);
const teacherId = page.text.match(/Guru Uji[\s\S]*?name="teacher_id" value="(\d+)"/)[1];
assert.equal((await admin.visit(classPath, { action: 'assign-teacher', teacher_id: teacherId })).response.status, 303);
assert.match((await teacher.visit('/guru')).text, /XI-PPLG-B/);
assert.equal((await teacher.visit(classPath)).response.status, 403);
assert.equal((await teacher.visit('/guru/kuis')).response.status, 200);
const teacherQuiz = await teacher.visit('/guru/kuis', {
  action: 'create', slug: 'quiz-guru-uji', title: 'Kuis Guru Uji', summary: 'Latihan.',
  topic: 'pemrograman', level: 'dasar', attempt_limit: '3', minutes: '5', passing: '60',
  class_id: classPath.split('/').at(-1),
});
assert.equal(teacherQuiz.response.status, 303);
const teacherQuizPath = teacherQuiz.response.headers.get('location').replace(/\?saved=1$/, '');
assert.match(teacherQuizPath, /^\/guru\/kuis\/\d+$/);
assert.equal((await teacher.visit('/guru/kuis/1')).response.status, 404);
const draft = (await teacher.visit(teacherQuizPath)).text.match(/name="draft_id" value="(\d+)"/)[1];
assert.equal((await teacher.visit(teacherQuizPath, { action: 'question-save', draft_id: draft, stem: 'Berapa 2 + 2?', a: '4', b: '5', correct: 'a', explanation: 'Hasilnya empat.' })).response.status, 303);
assert.equal((await teacher.visit(teacherQuizPath, { action: 'publish', draft_id: draft })).response.status, 303);
const questTime = offset => new Date(Date.now() + (7 + offset) * 3600000).toISOString().slice(0, 16);
const questInput = { action: 'create', title: 'Quest khusus B', quiz_id: teacherQuizPath.split('/').at(-1), class_id: classPath.split('/').at(-1), starts_at: questTime(-1), ends_at: questTime(1), target: '1', xp: '30' };
assert.equal((await teacher.visit('/guru/quest')).response.status, 200);
assert.equal((await teacher.visit('/guru/quest', { ...questInput, class_id: '0' })).response.status, 403);
assert.equal((await teacher.visit('/guru/quest', questInput)).response.status, 303);
assert.equal((await teacher.visit('/guru/quest', { ...questInput, title: 'Quest baca B', kind: 'review', xp: '40' })).response.status, 303);
assert.equal((await teacher.visit('/guru/quest', { ...questInput, title: 'Quest jawab B', kind: 'answers', xp: '10' })).response.status, 303);
assert.doesNotMatch((await student.visit('/')).text, /Quest khusus B/);
assert.equal((await student.visit('/kuis/quiz-guru-uji')).response.status, 404);
assert.equal((await admin.visit(classPath, { action: 'assign', student_id: '1' })).response.status, 303);
assert.match((await student.visit('/')).text, /Quest khusus B/);
assert.match((await admin.visit(classPath)).text, /Satrio/);
const started = await student.visit('/kuis/quiz-guru-uji/mulai', {});
assert.equal(started.response.status, 303);
const exercise = await student.visit(started.response.headers.get('location'));
const question = exercise.text.match(/name="question" value="(\d+)"/)[1];
const option = exercise.text.match(/name="option" value="(\d+)"/)[1];
assert.equal((await student.visit(started.response.headers.get('location') + '/jawaban', { question, option, target: '1' })).response.status, 303);
assert.equal((await student.visit(started.response.headers.get('location') + '/kumpulkan', {})).response.status, 303);
assert.match((await student.visit('/')).text, /30 XP[\s\S]*Selesai/);
const resultPath = '/hasil/' + started.response.headers.get('location').split('/').at(-1);
assert.equal((await student.visit(resultPath + '/selesai-baca', { csrf: 'invalid' })).response.status, 403);
assert.equal((await student.visit(resultPath + '/selesai-baca', {})).response.status, 303);
assert.equal((await student.visit(resultPath + '/selesai-baca', {})).response.status, 303);
assert.match((await student.visit('/')).text, /Total XP quest:[\s\S]*80 XP/);
const questPage = (await teacher.visit('/guru/quest')).text;
const questId = questPage.match(/Quest khusus B[\s\S]*?name="quest_id" value="(\d+)"/)[1];
assert.equal((await teacher.visit('/guru/quest', { action: 'archive', quest_id: questId })).response.status, 303);
const dashboardAfterArchive = (await student.visit('/')).text;
assert.doesNotMatch(dashboardAfterArchive, /Quest khusus B/);
assert.match(dashboardAfterArchive, /Total XP quest:[\s\S]*80 XP/);
assert.match((await teacher.visit(teacherQuizPath)).text, /Satrio/);
assert.equal((await admin.visit(classPath, { action: 'remove', student_id: '1' })).response.status, 303);
assert.doesNotMatch((await student.visit('/')).text, /Quest khusus B/);
assert.doesNotMatch((await teacher.visit(teacherQuizPath)).text, /Satrio/);
const quizPath = '/admin/kuis/1';
assert.equal((await admin.visit(quizPath)).response.status, 200);
const classId = classPath.split('/').at(-1);
const otherCreated = await admin.visit('/admin/kelas', { action: 'create', name: 'XI-PPLG-A', year: '2026/2027' });
assert.equal(otherCreated.response.status, 303);
const otherId = otherCreated.response.headers.get('location').match(/\/kelas\/(\d+)/)[1];
assert.equal((await teacher.visit('/guru/kuis', {
  action: 'create', slug: 'kelas-lain', title: 'Kuis kelas lain', summary: 'Latihan.',
  topic: 'pemrograman', level: 'dasar', attempt_limit: '3', minutes: '5', passing: '60', class_id: otherId,
})).response.status, 404);
assert.equal((await teacher.visit(teacherQuizPath, { action: 'archive', confirm: '1' })).response.status, 403);
assert.equal((await teacher.visit(teacherQuizPath, { action: 'publish', draft_id: draft, csrf: 'invalid' })).response.status, 403);
assert.equal((await admin.visit(quizPath, { action: 'audience', audience: 'selected_classes', classes: classId })).response.status, 303);
assert.equal((await student.visit('/kuis/struktur-data')).response.status, 404);
assert.equal((await admin.visit(classPath, { action: 'assign', student_id: '1' })).response.status, 303);
assert.equal((await student.visit('/kuis/struktur-data')).response.status, 200);
assert.equal((await admin.visit(quizPath, { action: 'audience', audience: 'all_classes', classes: classId })).response.status, 303);
assert.equal((await admin.visit(classPath, { action: 'remove-teacher', teacher_id: teacherId })).response.status, 303);
assert.equal((await teacher.visit(teacherQuizPath)).response.status, 404);
assert.equal((await admin.visit('/admin/guru/' + teacherId, { action: 'disable', confirm: '1' })).response.status, 303);
assert.equal((await teacher.visit('/guru')).response.status, 303);
assert.equal((await admin.visit(classPath, { action: 'archive', confirm: '1' })).response.status, 303);
assert.match((await admin.visit(classPath)).text, /Diarsipkan/);
assert.equal((await admin.visit(classPath, { action: 'restore' })).response.status, 303);
const dates = new Date(Date.now() + (48 + 7) * 3600000).toISOString().slice(0, 10);
const exam = await admin.visit('/admin/jadwal', {
  action: 'create', title: 'Ujian kelas B', location: 'Lab', note: 'Uji kelas',
  starts: dates + 'T09:00', ends: dates + 'T10:00',
});
assert.equal(exam.response.status, 303);
const examPath = exam.response.headers.get('location').replace(/\?saved=1$/, '');
assert.equal((await admin.visit(examPath, { action: 'audience', audience: 'selected_classes', classes: otherId })).response.status, 303);
assert.equal((await admin.visit(examPath, { action: 'publish' })).response.status, 303);
assert.doesNotMatch((await student.visit('/jadwal')).text, /Ujian kelas B/);
const otherPath = '/admin/kelas/' + otherId;
assert.equal((await admin.visit(otherPath, { action: 'assign', student_id: '1' })).response.status, 303);
assert.match((await student.visit('/jadwal')).text, /Ujian kelas B/);
console.log('PASS: admin can create a class and manage a student membership');
