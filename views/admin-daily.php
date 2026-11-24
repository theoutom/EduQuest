<a class="breadcrumb" href="/admin">Administrasi</a>
<header class="page-header"><h1>Tantangan harian</h1></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <section class="panel" aria-labelledby="schedule-title">
        <h2 id="schedule-title">Jadwalkan kuis</h2>
        <?php if (!$quizzes): ?>
            <p class="muted">Terbitkan kuis berdurasi lima menit atau kurang untuk mengisi tantangan.</p>
            <a class="button button-quiet tutor-trigger" href="/admin/kuis">Kelola kuis</a>
        <?php else: ?>
            <form class="admin-form" action="/admin/tantangan" method="post">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="action" value="save">
                <label class="field">Tanggal (WIB)<input name="day" type="date" required value="<?= h(formValue($submitted, 'day', (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d'))) ?>"></label>
                <label class="field">Kuis
                    <select name="quiz_id" required>
                        <option value="">Pilih kuis</option>
                        <?php foreach ($quizzes as $quiz): ?>
                            <option value="<?= h($quiz['id']) ?>" <?= (string) formValue($submitted, 'quiz_id') === (string) $quiz['id'] ? 'selected' : '' ?>><?= h($quiz['title']) ?> · <?= h(formatDuration($quiz['duration_seconds'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button button-primary" type="submit">Simpan jadwal</button>
            </form>
        <?php endif; ?>
    </section>
    <section class="panel" aria-labelledby="dates-title">
        <h2 id="dates-title">Hari ini dan mendatang</h2>
        <?php if (!$schedule): ?><p class="muted">Belum ada jadwal.</p><?php endif; ?>
        <ul class="admin-list">
            <?php foreach ($schedule as $entry): ?>
                <li class="admin-item">
                    <time datetime="<?= h($entry['challenge_date']) ?>"><?= h((new DateTimeImmutable($entry['challenge_date']))->format('d/m/Y')) ?></time>
                    <p><?= h($entry['title']) ?></p>
                    <form method="post" action="/admin/tantangan" class="admin-form">
                        <?php csrfField($csrf); ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="day" value="<?= h($entry['challenge_date']) ?>">
                        <button class="button button-quiet" type="submit">Cabut jadwal</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
