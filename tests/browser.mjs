import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import puppeteer from 'puppeteer-core';

assert.ok(existsSync(fileURLToPath(new URL('../public/assets/app.js', import.meta.url))), 'Quiz browser enhancement is available');
const base = process.env.EDUQUEST_TEST_URL ?? 'http://127.0.0.1:8080';
const executablePath = [
  process.env.CHROME_PATH,
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  '/usr/bin/chromium',
  '/usr/bin/google-chrome',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
].find(path => path && existsSync(path));
assert.ok(executablePath, 'Set CHROME_PATH to Chrome or Chromium');
const browser = await puppeteer.launch({ executablePath, headless: true });
const page = await browser.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
const snapshot = async name => {
  if (process.env.EDUQUEST_SCREENSHOTS !== '1') return;
  await mkdir('test-artifacts', { recursive: true });
  await page.screenshot({ path: 'test-artifacts/' + name + '.png', fullPage: true });
};
const visit = path => page.goto(new URL(path, base).href);
const navigate = selector => Promise.all([page.waitForNavigation(), page.click(selector)]);
const status = text => page.waitForFunction(expected => document.querySelector('[data-save-status]').textContent.includes(expected), {}, text);
const tabTo = async (targetPage, selector) => {
  for (let step = 0; step < 80; step++) {
    if (await targetPage.evaluate(target => document.activeElement.matches(target), selector)) return;
    await targetPage.keyboard.press('Tab');
  }
  assert.fail('Keyboard could not reach ' + selector);
};

try {
  await page.setViewport({ width: 1440, height: 900 });
  await visit('/masuk');
  await page.keyboard.press('Tab');
  assert.equal(await page.$eval(':focus', node => node.textContent), 'Lompat ke konten');
  await page.keyboard.press('Tab');
  assert.equal(await page.$eval(':focus', node => node.name), 'username');
  await page.keyboard.type('nadia');
  await page.keyboard.press('Tab');
  await page.keyboard.type(process.env.EDUQUEST_TEST_PASSWORD);
  await page.keyboard.press('Tab');
  await Promise.all([page.waitForNavigation(), page.keyboard.press('Enter')]);
  assert.match(await page.title(), /Dashboard/);
  assert.equal(await page.$$eval('.activity-list time', days => days.length), 7);
  assert.equal(await page.$eval('#daily-title', node => node.textContent), 'Tantangan harian');
  assert.match(await page.$eval('#quest-list', node => node.textContent), /Belum ada quest aktif/);
  await snapshot('dashboard-desktop');

  await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  await snapshot('dashboard-mobile');
  await visit('/profil');
  await page.type('main [name="school"]', 'SMK Uji Browser');
  await navigate('form[action="/profil"] button');
  assert.equal(await page.$eval('main [name="school"]', input => input.value), 'SMK Uji Browser');
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  await visit('/');
  await page.click('.mobile-nav summary');
  await navigate('.mobile-nav-panel a[href="/kuis"]');
  await snapshot('katalog-mobile');
  const bookmarkForm = 'form[action="/kuis/sql-relasional/simpanan"] button';
  await navigate(bookmarkForm);
  assert.equal(await page.$eval(bookmarkForm, button => button.value), '0');
  await navigate(bookmarkForm);
  assert.equal(await page.$eval(bookmarkForm, button => button.value), '1');
  await navigate('.quiz-card a[href="/kuis/sql-relasional"]');
  await navigate('form[action="/kuis/sql-relasional/mulai"] button');
  assert.match(page.url(), /\/sesi\/\d+$/);
  const session = page.url();
  const deadline = await page.$eval('[data-clock]', node => node.dataset.deadline);
  const clockText = await page.$eval('[data-clock]', node => node.textContent);
  await page.waitForFunction(previous => document.querySelector('[data-clock]').textContent !== previous, {}, clockText);
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  await snapshot('soal-mobile');

  const regressions = [];
  let fault = 'before';
  await page.setRequestInterception(true);
  page.on('request', async request => {
    if (fault && request.method() === 'POST' && request.url().endsWith('/jawaban')) {
      const currentFault = fault;
      fault = null;
      if (currentFault === 'after') {
        const cookies = await page.browserContext().cookies();
        const saved = await fetch(request.url(), {
          method: 'POST',
          body: new URLSearchParams(await request.fetchPostData()),
          headers: { accept: 'application/json', cookie: cookies.map(cookie => cookie.name + '=' + cookie.value).join('; ') },
        });
        assert.deepEqual(await saved.json(), { saved: true });
      }
      return request.respond({ status: 503, contentType: 'application/json', body: '{"message":"Gangguan uji"}' });
    }
    return request.continue();
  });
  await page.click('.choice:nth-of-type(2) input');
  await status('Gangguan uji');
  assert.equal(await page.$eval('.choice:nth-of-type(2) input', node => node.checked), true);
  assert.equal(new URL(page.url()).pathname, new URL(session).pathname);
  await page.click('[data-retry]');
  await status('Tersimpan');
  assert.equal(await page.$eval('[data-progress]', node => node.textContent), '1/3');
  await page.click('[name="doubtful"]');
  await status('Tersimpan');
  await page.reload();
  assert.equal(await page.$eval('.choice:nth-of-type(2) input', node => node.checked), true);
  assert.equal(await page.$eval('[name="doubtful"]', node => node.checked), true);
  assert.equal(await page.$eval('[data-clock]', node => node.dataset.deadline), deadline);
  assert.equal(await page.$eval('[data-current-question]', node => node.dataset.state), 'ragu');

  fault = 'after';
  await page.click('.choice:nth-of-type(1) input');
  await status('Gangguan uji');
  await page.click('.choice:nth-of-type(2) input');
  await status('Tersimpan');
  await page.reload();
  if (!await page.$eval('.choice:nth-of-type(2) input', node => node.checked)) {
    regressions.push('Reverting after a lost acknowledgement must write the restored answer');
    await page.click('.choice:nth-of-type(2) input');
    await status('Tersimpan');
  }

  await page.click('[data-clear]');
  await status('Tersimpan');
  assert.equal(await page.$('input[name="option"]:checked'), null);
  await page.click('.choice:nth-of-type(2) input');
  const next = page.waitForNavigation();
  await page.click('.question-actions button[value="2"]');
  await next;
  assert.match(page.url(), /soal=2$/);
  assert.equal(await page.$('[data-tutor-open]'), null);
  await page.click('.question-number[value="3"]');
  await page.waitForFunction(() => location.search === '?soal=3');
  const peer = await browser.newPage();
  await peer.goto(session + '?soal=2');
  await peer.click('.choice:nth-of-type(1) input');
  await peer.waitForFunction(() => document.querySelector('[data-save-status]').textContent === 'Tersimpan');
  await peer.close();
  await page.click('.question-actions button[value="kumpulkan"]');
  await page.waitForSelector('[data-confirm][open]');
  if (!/1 soal belum dijawab/.test(await page.$eval('[data-confirm-copy]', node => node.textContent))) {
    regressions.push('Confirmation must count answers saved from a second tab');
  }
  await page.click('[data-confirm] form[method="dialog"] button');
  assert.equal(await page.$eval('[data-confirm]', dialog => dialog.open), false);
  await page.click('.question-actions button[value="kumpulkan"]');
  await page.waitForSelector('[data-confirm][open]');
  await navigate('[data-submit-form] button');
  assert.match(page.url(), /\/hasil\/\d+$/);
  assert.equal(await page.$eval('.score strong', node => node.textContent), '33');
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  await snapshot('pembahasan-mobile');
  await page.setViewport({ width: 1440, height: 900 });
  await snapshot('pembahasan-desktop');
  await navigate('a.review-print');
  await page.evaluate(() => { window.print = () => { document.body.dataset.printCalled = '1'; }; });
  await page.click('[data-print-review]');
  assert.equal(await page.$eval('body', node => node.dataset.printCalled), '1');
  await page.emulateMediaType('print');
  assert.equal(await page.$eval('.sidebar', node => getComputedStyle(node).display), 'none');
  assert.equal(Buffer.from(await page.pdf()).subarray(0, 5).toString(), '%PDF-');
  await page.emulateMediaType('screen');

  await visit('/kuis/http-api');
  await tabTo(page, 'form[action="/kuis/http-api/mulai"] button');
  await Promise.all([page.waitForNavigation(), page.keyboard.press('Enter')]);
  await tabTo(page, 'input[name="option"]');
  await page.keyboard.press('Space');
  await status('Tersimpan');
  await tabTo(page, 'button[form="answer-form"][value="kumpulkan"]');
  await page.keyboard.press('Enter');
  await page.waitForSelector('[data-confirm][open]');
  assert.equal(await page.$eval(':focus', node => node.textContent), 'Kembali ke soal');
  await tabTo(page, '[data-submit-form] button');
  await Promise.all([page.waitForNavigation(), page.keyboard.press('Enter')]);
  assert.match(page.url(), /\/hasil\/\d+$/);
  assert.equal(await page.$eval('main h1', node => node.textContent), 'Hasil & Pembahasan');

  const adminContext = await browser.createBrowserContext();
  const admin = await adminContext.newPage();
  admin.on('pageerror', error => errors.push(error.message));
  await admin.setViewport({ width: 1440, height: 900 });
  await admin.goto(base + '/masuk');
  await admin.type('[name="username"]', 'operator');
  await admin.type('[name="password"]', process.env.EDUQUEST_TEST_PASSWORD);
  await Promise.all([admin.waitForNavigation(), admin.click('form button[type="submit"]')]);
  await admin.goto(base + '/admin/quest');
  assert.match(await admin.$eval('h1', node => node.textContent), /Quest/);
  await admin.type('[name="title"]', 'Quest uji browser');
  await admin.select('[name="quiz_id"]', '1');
  await admin.$eval('[name="starts_at"]', input => { input.value = '2030-01-01T08:00'; });
  await admin.$eval('[name="ends_at"]', input => { input.value = '2030-01-02T08:00'; });
  await admin.type('[name="target"]', '1');
  await admin.type('[name="xp"]', '20');
  await Promise.all([admin.waitForNavigation(), admin.click('form[action="/admin/quest"] button[type="submit"]')]);
  assert.match(await admin.$eval('main', node => node.textContent), /Quest uji browser/);
  await admin.goto(base + '/admin');
  await Promise.all([admin.waitForNavigation(), admin.click('main a[href="/admin/kuis"]')]);
  const addQuiz = 'form[action="/admin/kuis"]';
  await admin.type(addQuiz + ' [name="slug"]', 'editor-browser');
  await admin.type(addQuiz + ' [name="title"]', 'Kuis dari browser');
  await admin.type(addQuiz + ' [name="summary"]', 'Dibuat melalui formulir admin.');
  await admin.select(addQuiz + ' [name="topic"]', 'pemrograman');
  await admin.click(addQuiz + ' [name="tutor_enabled"]');
  await Promise.all([admin.waitForNavigation(), admin.click(addQuiz + ' button[type="submit"]')]);
  assert.match(admin.url(), /\/admin\/kuis\/\d+\?saved=1$/);
  await admin.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  const compose = 'details[open] form:has([value="question-save"])';
  assert.equal(await admin.$eval(compose + ' [name="c"]', input => input.closest('[data-option-row]').hidden), true);
  await admin.click(compose + ' [data-add-option]');
  assert.equal(await admin.$eval(compose + ' [name="c"]', input => input.closest('[data-option-row]').hidden), false);
  assert.equal(await admin.$eval(':focus', input => input.name), 'c');
  await admin.type(compose + ' [name="c"]', '4');
  await admin.type(compose + ' [name="stem"]', 'Apa contoh bilangan genap?');
  await admin.type(compose + ' [name="a"]', '2');
  await admin.type(compose + ' [name="b"]', '3');
  await admin.click(compose + ' [name="correct"][value="a"]');
  await admin.type(compose + ' [name="explanation"]', 'Bilangan 2 habis dibagi dua.');
  await Promise.all([admin.waitForNavigation(), admin.click(compose + ' button[type="submit"]')]);
  assert.match(await admin.$eval('#questions-title + .admin-list summary', node => node.textContent), /bilangan genap/);
  assert.equal(await admin.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  const publish = 'form:has(input[name="action"][value="publish"]) button';
  await tabTo(admin, publish);
  await Promise.all([admin.waitForNavigation(), admin.keyboard.press('Enter')]);
  assert.match(await admin.$eval('#questions-title', node => node.textContent), /versi terbit/);
  await admin.goto(base + '/admin/kelas');
  await admin.type('input[name="name"]', 'XI-Browser');
  await admin.type('input[name="year"]', '2026/2027');
  await Promise.all([admin.waitForNavigation(), admin.click('form[action="/admin/kelas"] button')]);
  assert.match(admin.url(), /\/admin\/kelas\/\d+\?saved=1$/);
  assert.equal(await admin.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);

  await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  await visit('/komunitas');
  await navigate('main a[href="/komunitas/buat"]');
  await page.type('main [name="title"]', 'Kontribusi browser');
  await page.select('main [name="topic"]', 'pemrograman');
  await page.type('main [name="stem"]', 'Apa contoh angka genap?');
  await page.type('main [name="a"]', '2');
  await page.type('main [name="b"]', '3');
  await page.click('main [name="correct"][value="a"]');
  await page.type('main [name="explanation"]', 'Dua habis dibagi dua.');
  await navigate('main form button[type="submit"]');
  const contribution = new URL(page.url()).pathname;
  assert.match(contribution, /\/komunitas\/\d+$/);
  assert.match(await page.$eval('main aside', node => node.textContent), /Menunggu tinjauan/);
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  await admin.goto(base + '/admin/komunitas');
  await Promise.all([admin.waitForNavigation(), admin.click('main a[href="/admin' + contribution + '"]')]);
  const approve = 'form:has([name="action"][value="publish"]) button';
  await Promise.all([admin.waitForNavigation(), admin.click(approve)]);
  await page.reload();
  assert.match(await page.$eval('main .tag-row', node => node.textContent), /Disetujui/);
  await page.click('[data-tutor-open]');
  assert.equal(await page.$eval('[data-tutor]', dialog => dialog.open), true);
  await page.click('[data-tutor] button[value="mulai"]');
  await page.waitForSelector('[data-tutor-result]:not([hidden])');
  assert.match(await page.$eval('[data-tutor-hint]', node => node.textContent), /masukan|keluaran/);
  await page.keyboard.press('Escape');
  assert.equal(await page.$eval('[data-tutor]', dialog => dialog.open), false);
  await visit('/kuis/editor-browser');
  await navigate('form[action="/kuis/editor-browser/mulai"] button');
  await page.click('.choice:first-of-type input');
  await page.click('[data-tutor-open]');
  await page.waitForSelector('[data-tutor][open]');
  await page.click('[data-tutor] button[value="langkah"]');
  await page.waitForSelector('[data-tutor-result]:not([hidden])');
  assert.equal(await page.$eval('.choice:first-of-type input', input => input.checked), true);
  await page.keyboard.press('Escape');

  await admin.goto(base + '/admin/mentor');
  const newMentor = 'form[action="/admin/mentor"]';
  await admin.type(newMentor + ' [name="name"]', 'Mentor dari browser');
  await admin.select(newMentor + ' [name="category"]', 'pemrograman');
  await admin.type(newMentor + ' [name="bio"]', 'Bimbingan logika dasar.');
  await Promise.all([admin.waitForNavigation(), admin.click(newMentor + ' button[type="submit"]')]);
  const mentorPath = new URL(admin.url()).pathname.replace('/admin', '');
  assert.match(mentorPath, /^\/mentor\/\d+$/);
  const starts = new Date(Date.now() + (3 * 24 + 7) * 3600000).toISOString().slice(0, 16);
  await admin.$eval('input[name="starts"]', (input, value) => { input.value = value; }, starts);
  const addSlot = 'form:has([name="action"][value="add-slot"]) button[type="submit"]';
  await Promise.all([admin.waitForNavigation(), admin.click(addSlot)]);
  assert.match(await admin.$eval('main .admin-list', node => node.textContent), /Terbuka/);
  assert.equal(await admin.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  await visit('/mentor');
  await navigate('main h2 a[href="' + mentorPath + '"]');
  await page.click('#mentor-slots + p + .admin-list summary, #mentor-slots + .admin-list summary');
  await navigate('form[action="' + mentorPath + '"] button[type="submit"]');
  assert.match(page.url(), /\/mentor\/sesi$/);
  assert.match(await page.$eval('main', node => node.textContent), /Menunggu konfirmasi/);
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);

  await adminContext.close();
  assert.deepEqual(errors, []);
  assert.deepEqual(regressions, []);
  console.log('PASS: browser student workflow, responsive authoring, community moderation and mentor booking');
} finally {
  await browser.close();
}
