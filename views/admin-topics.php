<header class="page-header"><h1>Kelola Topik</h1><p class="muted">Tambah atau perbarui topik katalog kuis.</p></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <section class="panel" aria-labelledby="new-topic">
        <h2 id="new-topic">Tambah topik</h2>
        <?php $values = formValue($submitted, 'action') === 'create' ? $submitted : []; ?>
        <form class="admin-form" method="post" action="/admin/topik">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="create">
            <label class="field">Kode topik<input name="slug" maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= h(formValue($values, 'slug')) ?>" required></label>
            <label class="field">Nama topik<input name="name" maxlength="80" value="<?= h(formValue($values, 'name')) ?>" required></label>
            <button class="button button-primary" type="submit">Tambah topik</button>
        </form>
    </section>
    <section class="panel" aria-labelledby="topic-list">
        <h2 id="topic-list">Daftar topik</h2>
        <?php if (!$items): ?><p class="muted">Belum ada topik.</p><?php endif; ?>
        <div class="admin-list">
            <?php foreach ($items as $item): ?>
                <details class="admin-item" <?= formValue($submitted, 'slug') === $item['slug'] ? 'open' : '' ?>>
                    <summary><?= h($item['name']) ?> <span class="muted"><?= h($item['used']) ?> kuis, <?= h($item['contributions']) ?> soal komunitas</span></summary>
                    <?php $values = formValue($submitted, 'action') === 'update' && formValue($submitted, 'slug') === $item['slug'] ? $submitted : $item; ?>
                    <form class="admin-form" method="post" action="/admin/topik">
                        <?php csrfField($csrf); ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="slug" value="<?= h($item['slug']) ?>">
                        <label class="field">Nama topik<input name="name" maxlength="80" value="<?= h(formValue($values, 'name', $item['name'])) ?>" required></label>
                        <button class="button button-quiet" type="submit">Simpan nama</button>
                    </form>
                    <?php if ($item['used'] === 0 && $item['contributions'] === 0): ?>
                    <form class="admin-form" method="post" action="/admin/topik">
                        <?php csrfField($csrf); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="slug" value="<?= h($item['slug']) ?>">
                        <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi hapus topik</label>
                        <button class="button button-danger" type="submit">Hapus topik</button>
                    </form>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        </div>
    </section>
</div>
