<?php $endpoint = '/admin/mentor/' . $item['id']; ?>
<a class="breadcrumb" href="/admin/mentor">Daftar mentor</a>
<header class="page-header"><h1><?= h($item['name']) ?></h1><p class="muted"><?= $item['enabled'] ? 'Terlihat di direktori' : 'Nonaktif' ?></p></header>
<div class="admin-grid">
    <section class="panel">
        <h2>Informasi mentor</h2>
        <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
            <?php csrfField($csrf); ?><input type="hidden" name="action" value="update">
            <?php $values = $item; require __DIR__ . '/mentor-fields.php'; ?>
            <button class="button button-primary" type="submit">Simpan perubahan</button>
        </form>
        <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
            <?php csrfField($csrf); ?><input type="hidden" name="action" value="<?= $item['enabled'] ? 'disable' : 'enable' ?>">
            <button class="button <?= $item['enabled'] ? 'button-danger' : 'button-quiet' ?>" type="submit"><?= $item['enabled'] ? 'Nonaktifkan mentor' : 'Aktifkan mentor' ?></button>
        </form>
    </section>
    <section class="panel">
        <h2>Jadwal mendatang</h2>
        <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
            <?php csrfField($csrf); ?><input type="hidden" name="action" value="add-slot">
            <div class="admin-field-row">
                <label class="field">Waktu mulai (WIB)<input name="starts" type="datetime-local" required></label>
                <label class="field">Durasi<select name="minutes">
                    <?php foreach ([15, 30, 45, 60, 75, 90, 105, 120] as $minutes): ?><option value="<?= $minutes ?>" <?= $minutes === 60 ? 'selected' : '' ?>><?= $minutes ?> menit</option><?php endforeach; ?>
                </select></label>
            </div>
            <button class="button button-quiet" type="submit">Tambah slot</button>
        </form>
        <?php if (!$slots): ?><p class="muted tutor-trigger">Belum ada jadwal mendatang.</p><?php endif; ?>
        <ul class="admin-list">
        <?php foreach ($slots as $slot): ?>
            <li class="admin-item">
                <div class="section-heading"><strong><?= h(localDate($slot['starts_at'])) ?></strong><span class="tag tag-neutral"><?= $slot['booked'] ? 'Dipesan' : ($slot['enabled'] ? 'Terbuka' : 'Tutup') ?></span></div>
                <?php if (!$slot['booked']): ?>
                <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                    <?php csrfField($csrf); ?><input type="hidden" name="action" value="<?= $slot['enabled'] ? 'close-slot' : 'open-slot' ?>">
                    <input type="hidden" name="slot_id" value="<?= h($slot['id']) ?>">
                    <button class="button button-quiet" type="submit"><?= $slot['enabled'] ? 'Tutup slot' : 'Buka slot' ?></button>
                </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php require __DIR__ . '/community-pagination.php'; ?>
    </section>
</div>
