<a class="breadcrumb" href="/mentor">Cari mentor</a>
<header class="page-header"><h1>Sesi Mentor Saya</h1></header>
<div class="admin-stack">
    <?php if (!$items): ?><section class="panel"><h2>Belum ada sesi</h2><p class="muted">Pilih waktu dari direktori mentor untuk mengajukan sesi.</p><a class="button button-primary tutor-trigger" href="/mentor">Cari mentor</a></section><?php endif; ?>
    <?php foreach ($items as $item): ?>
        <article class="panel">
            <div class="section-heading"><h2><?= h($item['mentor_name']) ?></h2><span class="tag <?= $item['view_status'] === 'confirmed' ? 'tag-success' : 'tag-neutral' ?>"><?= h(Mentor::STATUSES[$item['view_status']]) ?></span></div>
            <p class="muted"><?= h(localDate($item['starts_at'])) ?> · <?= h(formatDuration(strtotime($item['ends_at']) - strtotime($item['starts_at']))) ?></p>
            <p><?= h(mentorRate($item['rate'])) ?></p>
            <?php if ($item['note'] !== ''): ?><p class="community-text"><?= h($item['note']) ?></p><?php endif; ?>
            <?php if ($item['message'] !== null): ?><p class="notice tutor-trigger"><?= h($item['message']) ?></p><?php endif; ?>
            <?php if ($item['meeting_url'] !== null): ?>
                <a class="button button-primary tutor-trigger" href="<?= h($item['meeting_url']) ?>" target="_blank" rel="noopener noreferrer">Gabung pertemuan <span class="sr-only">(tab baru)</span></a>
            <?php endif; ?>
            <?php if ($item['can_cancel']): ?>
                <form class="admin-form" method="post" action="/mentor/sesi">
                    <?php csrfField($csrf); ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= h($item['id']) ?>">
                    <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi pembatalan</label>
                    <button class="button button-danger" type="submit">Batalkan sesi</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/community-pagination.php'; ?>
