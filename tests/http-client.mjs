export const base = process.env.EDUQUEST_TEST_URL ?? 'http://127.0.0.1:8080';

export class Browser {
  cookies = new Map();
  csrf = '';

  async visit(path, payload, extra = {}) {
    const response = await fetch(new URL(path, base), {
      redirect: 'manual',
      method: payload ? 'POST' : 'GET',
      headers: {
        cookie: [...this.cookies].map(pair => pair.join('=')).join('; '),
        ...extra,
      },
      body: payload ? new URLSearchParams({ csrf: this.csrf, ...payload }) : undefined,
    });
    for (const cookie of response.headers.getSetCookie()) {
      const [name, value] = cookie.split(';')[0].split('=');
      this.cookies.set(name, value);
    }
    const text = await response.text();
    this.csrf = text.match(/name="csrf" value="([a-f0-9]+)"/)?.[1] ?? this.csrf;
    return { response, text };
  }

  async login(username) {
    await this.visit('/masuk');
    const { response } = await this.visit('/masuk', { username, password: process.env.EDUQUEST_TEST_PASSWORD });
    assert.equal(response.status, 303);
    assert.ok(response.headers.getSetCookie().some(cookie => /eduquest_session=.*httponly/i.test(cookie)));
  }
}
import assert from 'node:assert/strict';

