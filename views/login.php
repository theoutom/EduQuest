<div class="login-layout">
    <section class="login-context" aria-label="EduQuest">
        <span class="brand"><span class="brand-symbol" aria-hidden="true"></span>EduQuest</span>
        <div>
            <p class="login-lead">Latihan hari ini.<br>Paham untuk besok.</p>
            <p>Kerjakan soal, tinjau jawaban, dan pahami langkah yang terlewat.</p>
        </div>
    </section>
    <section class="panel login-form">
        <?php if (publicDemo()): ?>
        <h1>Demo EduQuest</h1>
        <p class="muted">Coba kuis dan lihat pembahasannya. Sesi demo berlangsung hingga satu jam.</p>
        <form method="post" class="admin-form" action="/masuk">
            <?php csrfField($csrf); ?>
            <button class="button button-primary" type="submit">Coba demo</button>
        </form>
        <?php else: ?>
        <h1><?= demoPreview() ? 'Masuk ke Demo EduQuest' : 'Masuk ke EduQuest' ?></h1>
        <p class="muted"><?= demoPreview()
            ? 'Gunakan akun demo dari pengelola untuk mencoba fitur dengan data contoh.'
            : 'Masuk dengan akun yang diberikan oleh pengelola EduQuest.' ?></p>
        <?php if ($message !== ''): ?><p class="notice notice-error" role="alert"><?= h($message) ?></p><?php endif; ?>
        <form class="grid gap-4" method="post" action="/masuk">
            <?php csrfField($csrf); ?>
            <label class="field">Nama pengguna
                <input name="username" autocomplete="username" maxlength="48" required>
            </label>
            <label class="field">Kata sandi
                <input name="password" type="password" autocomplete="current-password" maxlength="256" required>
            </label>
            <button class="button button-primary" type="submit">Masuk</button>
        </form>
        <?php endif; ?>
    </section>
</div>
