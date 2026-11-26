<header class="page-header"><h1>Mastery Tree</h1><p class="muted">Pelajari kompetensi secara bertahap dan lihat persyaratannya.</p></header>
<?php if (!$items): ?>
    <section class="panel"><p>Belum ada kompetensi untuk kelasmu.</p></section>
<?php else: ?>
    <?php $labels = ['mastered' => 'Dikuasai', 'available' => 'Tersedia', 'in_progress' => 'Sedang dipelajari', 'locked' => 'Terkunci', 'unavailable' => 'Belum tersedia']; ?>
    <ol class="admin-list">
    <?php foreach ($items as $item): ?>
        <li class="panel admin-item">
            <p class="muted"><?= h($item['topic_name']) ?> · <?= h($labels[$item['status']]) ?></p>
            <h2><?= h($item['title']) ?></h2>
            <p><?= h($item['description']) ?></p>
            <?php if ($item['needs_revision'] && $item['status'] !== 'mastered'): ?><p>Kuis telah diperbarui. Guru perlu memperbarui kompetensi ini.</p><?php endif; ?>
            <?php if ($item['passing_score'] !== null): ?><p>Nilai lulus kompetensi: <?= h($item['passing_score']) ?>/100.</p><?php endif; ?>
            <?php if ($item['prerequisites']): ?>
                <p>Prasyarat:</p>
                <ul><?php foreach ($item['prerequisites'] as $required): ?><li><?= h($required['title']) ?>: <?= $required['mastered'] ? 'dikuasai' : 'belum dikuasai' ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <?php if (in_array($item['status'], ['available', 'in_progress'], true) && $item['quiz_slug']): ?>
                <a class="button button-quiet" href="/kuis/<?= h($item['quiz_slug']) ?>">Buka kuis kompetensi</a>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ol>
<?php endif; ?>
