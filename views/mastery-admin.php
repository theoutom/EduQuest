<a class="breadcrumb" href="<?= $teacher ? '/guru' : '/admin' ?>">Kembali</a>
<header class="page-header"><h1>Kelola Mastery Tree</h1><p class="muted">Susun kompetensi, prasyarat, dan kuis untuk kelas.</p></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <section class="panel"><h2>Kompetensi baru</h2>
        <?php if ($teacher && !$classes): ?><p>Belum ada kelas yang ditugaskan.</p><?php else: ?>
            <?php require __DIR__ . '/mastery-form.php'; ?>
        <?php endif; ?>
    </section>
    <section class="panel"><h2>Kompetensi tersimpan</h2>
        <?php if (!$items): ?><p>Belum ada kompetensi.</p><?php endif; ?>
        <ul class="admin-list"><?php foreach ($items as $node): ?><li class="admin-item">
            <details><summary><?= h($node['title']) ?> · versi <?= h($node['version']) ?></summary>
                <?php if ($node['needs_revision']): ?><p>Kuis memiliki versi baru. Simpan versi kompetensi baru agar siswa dapat melanjutkan.</p><?php endif; ?>
                <p>Perubahan membuat versi baru; capaian yang sudah diraih tetap tersimpan.</p>
                <?php $item = $node; require __DIR__ . '/mastery-form.php'; ?>
            </details>
        </li><?php endforeach; ?></ul>
    </section>
</div>
