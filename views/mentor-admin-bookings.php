<a class="breadcrumb" href="/admin/mentor">Kelola mentor</a>
<header class="page-header"><h1>Pemesanan Mentor</h1></header>
<section class="panel" aria-labelledby="mentor-bookings">
    <h2 id="mentor-bookings">Permintaan dan riwayat sesi</h2>
    <?php if (!$items): ?><p class="muted">Belum ada pemesanan.</p><?php endif; ?>
    <ul class="admin-list admin-links">
    <?php foreach ($items as $item): ?>
        <li>
            <a href="/admin/mentor/sesi/<?= h($item['id']) ?>"><?= h($item['student_name']) ?> dengan <?= h($item['mentor_name']) ?></a>
            <p class="muted"><?= h(localDate($item['starts_at'])) ?></p>
            <span class="tag <?= $item['view_status'] === 'pending' ? 'tag-warning' : 'tag-neutral' ?>"><?= h(Mentor::STATUSES[$item['view_status']]) ?></span>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php require __DIR__ . '/community-pagination.php'; ?>
</section>
