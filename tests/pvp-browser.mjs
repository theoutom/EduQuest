import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { setTimeout as delay } from 'node:timers/promises';
import puppeteer from 'puppeteer-core';
import { Browser as Client, base } from './http-client.mjs';

const admin = new Client();
await admin.login('operator');
await admin.visit('/admin/kelas');
const created = await admin.visit('/admin/kelas', { action: 'create', name: 'PvP Browser', year: '2026/2027' });
assert.equal(created.response.status, 303);
const classPath = created.response.headers.get('location').replace(/\?saved=1$/, '');
const classId = classPath.split('/').at(-1);
for (const student_id of ['1', '2']) assert.equal((await admin.visit(classPath, { action: 'assign', student_id })).response.status, 303);
const executablePath = [process.env.CHROME_PATH, 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', '/usr/bin/chromium'].find(path => path && existsSync(path));
assert.ok(executablePath, 'Set CHROME_PATH to a Chromium browser.');
const browser = await puppeteer.launch({ executablePath, headless: true });
try {
  const contextA = await browser.createBrowserContext(), contextB = await browser.createBrowserContext();
  const alice = await contextA.newPage(), bob = await contextB.newPage();
  const errors = [];
  for (const page of [alice, bob]) {
    page.on('pageerror', error => errors.push(error.message));
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  }
  async function login(page, username) {
    await page.goto(base + '/masuk');
    await page.type('[name="username"]', username);
    await page.type('[name="password"]', process.env.EDUQUEST_TEST_PASSWORD);
    await Promise.all([page.waitForNavigation(), page.click('form button[type="submit"]')]);
  }
  await login(alice, 'satrio');
  await login(bob, 'nadia');
  await alice.goto(base + '/arena');
  await alice.select('[name="class_id"]', classId);
  await alice.select('[name="opponent"]', '2');
  await alice.select('[name="quiz_id"]', '1');
  await Promise.all([alice.waitForNavigation(), alice.click('form[action="/arena"] button')]);
  const matchUrl = alice.url();
  assert.match(matchUrl, /\/arena\/\d+$/);
  await bob.goto(matchUrl);
  await Promise.all([bob.waitForNavigation(), bob.click('form input[name="action"][value="accept"] + button')]);
  assert.match(await bob.$eval('main', n => n.innerText), /Persiapan ronde/);
  await delay(13000);
  await alice.goto(matchUrl);
  await bob.goto(matchUrl);
  assert.equal(await alice.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
  assert.equal(await bob.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
  await alice.click('input[name="option"]');
  await Promise.all([alice.waitForNavigation(), alice.click('form input[name="action"][value="answer"] + input + fieldset + button')]);
  assert.match(await alice.$eval('main', n => n.innerText), /Jawaban terkirim/);
  for (const page of [alice, bob]) {
    await Promise.all([page.waitForNavigation(), page.click('form input[name="action"][value="finish"] + button')]);
  }
  await alice.goto(matchUrl);
  assert.match(await alice.$eval('main', n => n.innerText), /Hasil pertandingan/);
  assert.deepEqual(errors, []);
  console.log('PASS: PvP browser interaction for two students on mobile viewport.');
} finally { await browser.close(); }
