import assert from 'node:assert/strict';
import { Browser } from './http-client.mjs';

const admin = new Browser();
const student = new Browser();
const peer = new Browser();
await admin.login('operator');
await student.login('satrio');
await admin.visit('/admin');
await student.visit('/mentor');
const go = (result, pattern) => {
  assert.equal(result.response.status, 303);
  const location = result.response.headers.get('location');
  assert.match(location, pattern);
  return location;
};
const peerPath = go(await admin.visit('/admin/siswa', {
  action: 'create', username: 'mentor-peer', name: 'Siswa Mentor', password: process.env.EDUQUEST_TEST_PASSWORD,
}), /^\/admin\/siswa\/\d+\?saved=1$/).split('?')[0];
await peer.login('mentor-peer');
await peer.visit('/mentor');
assert.equal((await student.visit('/admin/mentor')).response.status, 403);
const mentor = go(await admin.visit('/admin/mentor', {
  action: 'create', name: 'Mentor Fixture', category: 'pemrograman', bio: 'Bimbingan algoritma dasar.', rate: '50000',
}), /^\/admin\/mentor\/\d+$/);
const publicPath = mentor.replace('/admin', '');
const future = Date.now() + 2 * 86400000;
const jakartaInput = time => new Date(time + 7 * 3600000).toISOString().slice(0, 16);
const starts = jakartaInput(future);
const display = starts.slice(0, 10).split('-').reverse().join('/') + ' ' + starts.slice(11) + ' WIB';
assert.equal((await admin.visit(mentor, { action: 'add-slot', starts: starts + '\0', minutes: '60' })).response.status, 422);
go(await admin.visit(mentor, { action: 'add-slot', starts, minutes: '60' }), /\/admin\/mentor\//);
assert.equal((await admin.visit(mentor, { action: 'add-slot', starts: jakartaInput(future + 1800000), minutes: '30' })).response.status, 409);
assert.match((await student.visit('/mentor?q=fixture&kategori=pemrograman&tersedia=1')).text, /Mentor Fixture/);
const detail = await student.visit(publicPath);
assert.ok(detail.text.includes(display), 'Mentor schedules should display the entered local time in WIB');
const slot = detail.text.match(/name="slot_id" value="(\d+)"/)[1];
go(await student.visit(publicPath, { action: 'book', slot_id: slot, note: 'Bahas latihan pencarian.' }), /^\/mentor\/sesi$/);
assert.equal((await peer.visit(publicPath, { action: 'book', slot_id: slot })).response.status, 409);
const own = await student.visit('/mentor/sesi');
assert.match(own.text, /Menunggu konfirmasi/);
assert.ok(!(await student.visit('/')).text.includes('Mentor Fixture'));
const adminList = await admin.visit('/admin/mentor/sesi');
const booking = adminList.text.match(/href="(\/admin\/mentor\/sesi\/\d+)"/)[1];
const link = 'https://meet.google.com/abc-defg-hij';
assert.equal((await admin.visit(booking, { action: 'confirm', url: 'javascript:alert(1)' })).response.status, 422);
go(await admin.visit(booking, { action: 'confirm', url: link }), /^\/admin\/mentor\/sesi\//);
assert.equal((await admin.visit(booking, { action: 'confirm', url: link })).response.status, 409);
assert.doesNotMatch((await admin.visit(booking)).text, /abc-defg-hij/);
assert.match((await student.visit('/mentor/sesi')).text, /href="https:\/\/meet.google.com\/abc-defg-hij"/);
const home = await student.visit('/');
assert.ok(home.text.includes('Sesi mentor berikutnya') && home.text.includes('Mentor Fixture'));
assert.ok(!home.text.includes(link), 'Dashboard should link to Sesi saya without exposing the meeting URL');
assert.ok(!(await peer.visit('/')).text.includes('Mentor Fixture'));
assert.doesNotMatch((await peer.visit('/mentor/sesi')).text, /abc-defg-hij/);
assert.doesNotMatch((await peer.visit(publicPath)).text, /abc-defg-hij/);
const id = booking.split('/').at(-1);
assert.equal((await peer.visit('/mentor/sesi', { action: 'cancel', booking_id: id, confirm: '1' })).response.status, 404);
go(await student.visit('/mentor/sesi', { action: 'cancel', booking_id: id, confirm: '1' }), /^\/mentor\/sesi$/);
assert.doesNotMatch((await student.visit('/mentor/sesi')).text, /abc-defg-hij/);
assert.ok(!(await student.visit('/')).text.includes('Mentor Fixture'));
go(await peer.visit(publicPath, { action: 'book', slot_id: slot }), /^\/mentor\/sesi$/);
assert.ok((await admin.visit(peerPath)).text.includes('1 pemesanan mentor'));
assert.equal((await admin.visit(peerPath, { action: 'delete', confirm: '1' })).response.status, 409);
assert.match((await peer.visit('/mentor/sesi?hal=2')).text, /Sebelumnya/);
console.log('PASS: mentor directory, slot conflict, booking, authorization, external link and cancellation');
