<a class="breadcrumb" href="/admin">Administrasi</a>
<header class="page-header"><h1>Kelola Jadwal Ujian</h1></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <section class="panel" aria-labelledby="new-exam">
        <h2 id="new-exam">Buat draf jadwal</h2>
        <form method="post" action="/admin/jadwal" class="admin-form">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="create">
            <?php require __DIR__ . '/exam-fields.php'; ?>
            <button class="button button-primary" type="submit">Simpan draf</button>
        </form>
    </section>
    <section class="panel" aria-labelledby="managed-exams">
        <h2 id="managed-exams">Daftar jadwal</h2>
        <?php if (!$items): ?><p class="muted">Belum ada jadwal ujian.</p><?php endif; ?>
        <ul class="admin-list admin-links">
            <?php foreach ($items as $entry): ?>
                <li>
                    <a href="/admin/jadwal/<?= h($entry['id']) ?>"><?= h($entry['title']) ?></a>
                    <p class="muted"><?= h(localDate($entry['starts_at'])) ?> · <?= $entry['published'] ? 'Terbit' : 'Draf' ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
