<header class="page-header"><h1>Moderasi Komunitas</h1><p class="muted">Periksa kunci dan pembahasan sebelum menerbitkan kontribusi.</p></header>
<nav class="filter-tabs" aria-label="Status pengajuan">
    <?php foreach (Community::STATES as $key => $label): ?>
        <a class="button button-quiet" href="/admin/komunitas?status=<?= h($key) ?>" <?= $key === $status ? 'aria-current="page"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
</nav>
<section class="panel">
    <h2><?= h(Community::STATES[$status]) ?></h2>
    <?php if (!$items): ?><p class="muted">Belum ada pengajuan pada status ini.</p><?php endif; ?>
    <ul class="admin-list admin-links">
        <?php foreach ($items as $item): ?><li><a href="/admin/komunitas/<?= h($item['id']) ?>"><?= h($item['title']) ?></a><p class="muted">Oleh <?= h($item['author']) ?></p></li><?php endforeach; ?>
    </ul>
</section>
<?php $pagingPath = '/admin/komunitas'; $pagingFilters = ['status' => $status]; $pagingKey = 'hal'; $pagingLabel = 'Halaman moderasi'; require __DIR__ . '/community-pagination.php'; ?>
