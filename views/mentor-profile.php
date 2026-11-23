<a class="breadcrumb" href="/mentor">Cari mentor</a>
<header class="page-header"><h1><?= h($item['name']) ?></h1><p class="muted"><?= h(Mentor::CATEGORIES[$item['category']]) ?></p></header>
<div class="detail-grid">
    <section class="panel" aria-labelledby="mentor-slots">
        <h2 id="mentor-slots">Jadwal tersedia</h2>
        <?php if (!$slots): ?><p class="muted">Belum ada slot terbuka.</p><?php endif; ?>
        <div class="admin-list">
            <?php foreach ($slots as $slot): ?>
            <details class="admin-item">
                <summary><?= h(localDate($slot['starts_at'])) ?> <span class="muted"><?= h(formatDuration(strtotime($slot['ends_at']) - strtotime($slot['starts_at']))) ?></span></summary>
                <form class="admin-form" method="post" action="/mentor/<?= h($item['id']) ?>">
                    <?php csrfField($csrf); ?>
                    <input type="hidden" name="action" value="book">
                    <input type="hidden" name="slot_id" value="<?= h($slot['id']) ?>">
                    <label class="field">Topik yang ingin dibahas (opsional)<textarea name="note" rows="3" maxlength="600"></textarea></label>
                    <button class="button button-primary" type="submit">Ajukan sesi</button>
                </form>
            </details>
            <?php endforeach; ?>
        </div>
        <?php require __DIR__ . '/community-pagination.php'; ?>
    </section>
    <aside class="panel">
        <h2>Profil mentor</h2>
        <?php if ($item['verified']): ?><p><span class="tag tag-success">Terverifikasi</span></p><?php endif; ?>
        <p class="community-text"><?= h($item['bio']) ?></p>
        <p class="community-stem"><strong><?= h(mentorRate($item['rate'])) ?></strong></p>
        <a class="button button-quiet tutor-trigger" href="/mentor/sesi">Buka sesi saya</a>
    </aside>
</div>
