<?php $endpoint = '/admin/mentor/sesi/' . $item['id']; ?>
<a class="breadcrumb" href="/admin/mentor/sesi">Pemesanan mentor</a>
<header class="page-header"><h1><?= h($item['student_name']) ?> dengan <?= h($item['mentor_name']) ?></h1><p class="muted"><?= h(localDate($item['starts_at'])) ?></p></header>
<div class="detail-grid">
    <section class="panel">
        <h2>Rincian pemesanan</h2>
        <p><?= h(Mentor::STATUSES[$item['view_status']]) ?> · <?= h(mentorRate($item['rate'])) ?></p>
        <?php if ($item['note'] !== ''): ?><p class="community-text"><?= h($item['note']) ?></p><?php endif; ?>
        <?php if ($item['message'] !== null): ?><p class="notice tutor-trigger"><?= h($item['message']) ?></p><?php endif; ?>
    </section>
    <section class="panel">
        <h2>Keputusan</h2>
        <?php if ($item['view_status'] === 'pending'): ?>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?><input type="hidden" name="action" value="confirm">
                <label class="field">URL pertemuan HTTPS<input name="url" type="url" maxlength="1000" required></label>
                <p class="muted">Google Meet, Zoom, Microsoft Teams, atau Jitsi Meet.</p>
                <button class="button button-primary" type="submit">Konfirmasi sesi</button>
            </form>
        <?php endif; ?>
        <?php if ($item['can_cancel']): ?>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?><input type="hidden" name="action" value="<?= $item['view_status'] === 'pending' ? 'reject' : 'cancel' ?>">
                <label class="field">Alasan untuk siswa<textarea name="reason" rows="3" maxlength="400" required></textarea></label>
                <button class="button button-danger" type="submit"><?= $item['view_status'] === 'pending' ? 'Tolak permintaan' : 'Batalkan sesi' ?></button>
            </form>
        <?php else: ?>
            <p class="muted">Pemesanan ini sudah ditutup untuk perubahan.</p>
        <?php endif; ?>
    </section>
</div>
