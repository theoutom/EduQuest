<a class="breadcrumb" href="/komunitas">Bank soal komunitas</a>
<header class="page-header"><h1>Pengajuan Saya</h1></header>
<section class="panel">
    <a class="button button-primary" href="/komunitas/buat">Bagikan soal</a>
    <?php if (!$items): ?><p class="muted">Kamu belum mengajukan soal.</p><?php endif; ?>
    <ul class="admin-list admin-links">
    <?php foreach ($items as $item): ?>
        <li><a href="/komunitas/<?= h($item['id']) ?>"><?= h($item['title']) ?></a>
            <span class="tag tag-neutral"><?= h(Community::STATES[$item['status']]) ?></span>
            <?php if ($item['moderation_note']): ?><p class="muted"><?= h($item['moderation_note']) ?></p><?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
</section>
<?php $pagingPath = '/komunitas/saya'; $pagingFilters = []; $pagingKey = 'hal'; $pagingLabel = 'Halaman pengajuan'; require __DIR__ . '/community-pagination.php'; ?>
