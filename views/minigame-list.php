<header class="page-header"><h1>Susun Langkah</h1><p class="muted">Susun langkah untuk menyelesaikan tantangan, lalu pelajari pembahasannya.</p></header>
<section class="panel"><h2>Permainan tersedia</h2>
    <?php if (!$items): ?><p>Belum ada permainan untuk kelasmu.</p><?php endif; ?>
    <ul class="admin-list"><?php foreach ($items as $item): ?><li class="admin-item">
        <h3><?= h($item['title']) ?></h3><p><?= h($item['instruction']) ?></p><p>Hadiah pertama: <?= h($item['xp']) ?> XP</p>
        <a class="button button-primary" href="/minigame/<?= h($item['id']) ?>">Mainkan</a>
        <?php if ($item['result_id'] !== null): ?><a class="button button-quiet" href="/minigame/hasil/<?= h($item['result_id']) ?>">Hasil terakhir</a><?php endif; ?>
    </li><?php endforeach; ?></ul>
</section>
