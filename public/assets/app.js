'use strict';

const navigation = document.querySelector('.mobile-nav');
const printReview = document.querySelector('[data-print-review]');
if (printReview) {
  printReview.hidden = false;
  printReview.addEventListener('click', () => window.print());
}
navigation?.addEventListener('keydown', event => {
  if (event.key === 'Escape' && navigation.open) {
    navigation.open = false;
    navigation.querySelector('summary').focus();
  }
});

const answerForm = document.querySelector('[data-answer-form]');
if (answerForm) {
  enableQuiz(answerForm);
}

function enableQuiz(form) {
  const status = form.querySelector('[data-save-status]');
  const retry = form.querySelector('[data-retry]');
  const clear = form.querySelector('[data-clear]');
  const confirmation = document.querySelector('[data-confirm]');
  const finishForm = document.querySelector('[data-submit-form]');
  const clock = document.querySelector('[data-clock]');
  const progress = document.querySelector('[data-progress]');
  const currentQuestion = document.querySelector('[data-current-question]');
  const blankOutsideQuestion = Number(form.dataset.blank) - (form.dataset.startSelected === '1' ? 0 : 1);
  const sessionUrl = new URL(form.action).pathname.replace(/\/jawaban$/, '');
  const deadline = performance.now() + Number(clock.dataset.deadline) - Number(clock.dataset.now);
  const original = () => new URLSearchParams(new FormData(form)).toString();
  let saved = original();
  let uncertain = false;
  let pending = null;
  let advancing = false;
  let finishing = false;

  function report(message, tone = '') {
    status.textContent = message;
    status.dataset.tone = tone;
    retry.hidden = tone !== 'error';
  }

  function updateProgress(data) {
    const blank = blankOutsideQuestion + (data.has('option') ? 0 : 1);
    const answered = progress.max - blank;
    const state = data.has('doubtful') ? 'ragu' : (data.has('option') ? 'terjawab' : 'kosong');
    currentQuestion.dataset.state = state;
    currentQuestion.setAttribute('aria-label', 'Soal ' + currentQuestion.value + ', ' + (state === 'ragu' ? 'ragu-ragu' : state));
    document.querySelector('[data-answered]').textContent = answered;
    progress.value = answered;
  }

  async function saveUntilCurrent() {
    while (!finishing) {
      const snapshot = original();
      if (!uncertain && snapshot === saved) {
        updateProgress(new URLSearchParams(snapshot));
        report('Tersimpan', 'success');
        return true;
      }
      report('Menyimpan…');
      uncertain = true;
      try {
        const response = await fetch(form.action, {
          method: 'POST',
          headers: { Accept: 'application/json' },
          body: new URLSearchParams(snapshot),
        });
        const result = await response.json();
        if (response.status === 409) {
          finishing = true;
          window.location.assign(sessionUrl);
          return false;
        }
        if (!response.ok || !result.saved) {
          report(result.message || 'Jawaban belum tersimpan. Coba lagi.', 'error');
          return false;
        }
        saved = snapshot;
        uncertain = false;
        updateProgress(new URLSearchParams(saved));
      } catch {
        report('Koneksi terputus. Jawaban belum tersimpan.', 'error');
        return false;
      }
    }
    return false;
  }

  function save() {
    if (!pending) {
      pending = saveUntilCurrent().finally(() => { pending = null; });
    }
    return pending;
  }

  function refreshClear() {
    clear.disabled = !form.querySelector('input[name="option"]:checked');
  }

  form.addEventListener('change', () => {
    refreshClear();
    void save();
  });
  clear.hidden = false;
  refreshClear();
  clear.addEventListener('click', () => {
    const selected = form.querySelector('input[name="option"]:checked');
    if (selected) {
      selected.checked = false;
      selected.focus();
      refreshClear();
      void save();
    }
  });
  retry.addEventListener('click', () => { void save(); });

  async function openConfirmation() {
    const url = sessionUrl + '/kumpulkan?soal=' + currentQuestion.value;
    try {
      const response = await fetch(url, { headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error('Confirmation request failed');
      const result = await response.json();
      if (finishing) return;
      if (result.location) {
        finishing = true;
        window.location.assign(result.location);
        return;
      }
      document.querySelector('[data-confirm-copy]').textContent =
        result.blank + ' soal belum dijawab. Setelah dikumpulkan, jawaban tidak bisa diubah.';
      confirmation.showModal();
    } catch {
      if (!finishing) window.location.assign(url);
    }
  }

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (advancing || finishing) return;
    const target = event.submitter?.value ?? currentQuestion.value;
    advancing = true;
    try {
      if (!await save() || finishing) return;
      if (target === 'kumpulkan') {
        await openConfirmation();
      } else if (target === 'tutor') {
        const dialog = document.querySelector('[data-tutor]');
        if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
        else window.location.assign(sessionUrl + '/tutor?soal=' + currentQuestion.value);
      } else if (target !== currentQuestion.value) {
        window.location.assign(sessionUrl + '?soal=' + target);
      }
    } finally {
      advancing = false;
    }
  });
  finishForm.addEventListener('submit', event => {
    if (finishing) {
      event.preventDefault();
    }
    finishing = true;
  });

  function tick() {
    const seconds = Math.ceil(Math.max(0, deadline - performance.now()) / 1000);
    const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
    clock.textContent = minutes + ':' + String(seconds % 60).padStart(2, '0');
    clock.closest('[data-clock-box]').toggleAttribute('data-urgent', seconds <= 60);
    if (seconds === 0 && !finishing) {
      finishing = true;
      finishForm.submit();
    }
  }
  const interval = window.setInterval(() => {
    tick();
    if (finishing) window.clearInterval(interval);
  }, 250);
  tick();
}

const tutor = document.querySelector('[data-tutor]');
const tutorOpen = document.querySelector('[data-tutor-open]');
if (tutor && tutorOpen && typeof tutor.showModal === 'function') {
  const tutorForm = tutor.querySelector('[data-tutor-form]');
  const tutorStatus = tutor.querySelector('[data-tutor-status]');
  const tutorResult = tutor.querySelector('[data-tutor-result]');
  const choices = [...tutorForm.querySelectorAll('[name="intent"]')];
  if (tutorOpen.tagName === 'A') {
    tutorOpen.addEventListener('click', event => {
      event.preventDefault();
      tutor.showModal();
    });
  }
  tutorForm.addEventListener('submit', async event => {
    event.preventDefault();
    const control = event.submitter;
    const body = new URLSearchParams(new FormData(tutorForm));
    body.set('intent', control?.value ?? 'mulai');
    choices.forEach(button => { button.disabled = true; });
    tutorForm.setAttribute('aria-busy', 'true');
    tutorResult.hidden = true;
    tutorStatus.textContent = 'Menyiapkan petunjuk…';
    try {
      const response = await fetch(tutorForm.action, {
        method: 'POST', headers: { Accept: 'application/json' }, body,
      });
      const result = await response.json();
      if (!response.ok) {
        tutorStatus.textContent = result.message || 'Petunjuk belum tersedia.';
        return;
      }
      tutorResult.querySelector('[data-tutor-hint]').textContent = result.hint;
      tutorResult.querySelector('[data-tutor-prompt]').textContent = result.prompt;
      tutorResult.querySelector('[data-tutor-source]').textContent = {
        ollama: 'Tindak lanjut dipilih model lokal',
        '9router': 'Tindak lanjut dipilih melalui 9Router',
        vercel: 'Tindak lanjut dipilih melalui AI Gateway',
      }[result.source] ?? 'Panduan bawaan';
      tutorResult.hidden = false;
      tutorStatus.textContent = 'Petunjuk siap.';
    } catch {
      tutorStatus.textContent = 'Koneksi terputus. Coba lagi.';
    } finally {
      choices.forEach(button => { button.disabled = false; });
      tutorForm.removeAttribute('aria-busy');
      if (tutor.open) control?.focus();
    }
  });
}
