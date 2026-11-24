<?php $endpoint = '/admin/jadwal/' . $item['id']; ?>
<a class="breadcrumb" href="/admin/jadwal">Daftar jadwal</a>
<header class="page-header"><h1><?= h($item['title']) ?></h1><p class="muted"><?= $item['published'] ? 'Terbit' : 'Draf' ?></p></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <section class="panel">
        <h2>Rincian jadwal</h2>
        <form method="post" action="<?= h($endpoint) ?>" class="admin-form">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="update">
            <?php require __DIR__ . '/exam-fields.php'; ?>
            <button class="button button-primary" type="submit">Simpan perubahan</button>
        </form>
    </section>
    <section class="panel">
        <h2>Publikasi</h2>
        <form method="post" action="<?= h($endpoint) ?>" class="admin-form">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="<?= $item['published'] ? 'withdraw' : 'publish' ?>">
            <button class="button button-quiet" type="submit"><?= $item['published'] ? 'Tarik dari siswa' : 'Terbitkan jadwal' ?></button>
        </form>
        <?php if (!$item['published']): ?>
        <form method="post" action="<?= h($endpoint) ?>" class="admin-form">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="delete">
            <label class="check"><input name="confirm" value="1" type="checkbox" required> Konfirmasi hapus jadwal</label>
            <button class="button button-danger" type="submit">Hapus jadwal</button>
        </form>
        <?php endif; ?>
    </section>
</div>
