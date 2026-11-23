<header class="page-header"><h1>Kelola Kuis</h1><p class="muted">Buat draf, susun soal, lalu terbitkan ke katalog siswa.</p></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <section class="panel" aria-labelledby="new-quiz">
        <h2 id="new-quiz">Buat draf kuis</h2>
        <?php if (!$topics): ?>
            <p class="muted">Tambahkan minimal satu topik terlebih dahulu.</p>
            <a class="button button-primary" href="/admin/topik">Buka topik</a>
        <?php else: ?>
        <?php $values = $submitted; ?>
        <form class="admin-form" method="post" action="/admin/kuis">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="create">
            <label class="field">Kode kuis<input name="slug" maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= h(formValue($values, 'slug')) ?>" required></label>
            <?php require __DIR__ . '/admin-quiz-fields.php'; ?>
            <?php require __DIR__ . '/admin-quiz-time.php'; ?>
            <button class="button button-primary" type="submit">Buat draf</button>
        </form>
        <?php endif; ?>
    </section>
    <section class="panel" aria-labelledby="quiz-list">
        <h2 id="quiz-list">Daftar kuis</h2>
        <?php if (!$items): ?><p class="muted">Belum ada kuis.</p><?php endif; ?>
        <ul class="admin-list admin-links">
            <?php foreach ($items as $item): ?>
                <li>
                    <a href="/admin/kuis/<?= h($item['id']) ?>"><?= h($item['title']) ?></a>
                    <p class="muted"><?= h($item['topic_name']) ?></p>
                    <div class="tag-row">
                        <?php if ($item['archived_at'] !== null): ?><span class="tag tag-neutral">Diarsipkan</span><?php endif; ?>
                        <?php if ($item['published'] !== null): ?><span class="tag tag-success">Terbit v<?= h($item['published']) ?></span><?php endif; ?>
                        <?php if ($item['draft'] !== null): ?><span class="tag tag-warning">Draf v<?= h($item['draft']) ?></span><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
