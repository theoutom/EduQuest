<header class="page-header"><h1>Kelola Guru</h1></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<section class="panel">
    <h2>Buat akun guru</h2>
    <form class="admin-form" method="post" action="/admin/guru">
        <?php csrfField($csrf); ?>
        <input type="hidden" name="action" value="create">
        <label class="field">Username<input name="username" maxlength="48" required></label>
        <label class="field">Nama<input name="name" maxlength="80" required></label>
        <label class="field">Kata sandi awal<input name="password" type="password" minlength="15" maxlength="256" required></label>
        <button class="button button-primary" type="submit">Buat akun guru</button>
    </form>
</section>
<section class="panel">
    <h2>Daftar guru</h2>
    <ul class="admin-list">
        <?php foreach ($items as $item): ?>
        <li>
            <span><?= h($item['name']) ?> · <?= h($item['username']) ?><?= $item['enabled'] ? '' : ' · Nonaktif' ?></span>
            <form class="admin-form" method="post" action="/admin/guru/<?= h($item['id']) ?>">
                <?php csrfField($csrf); ?><input type="hidden" name="action" value="reset">
                <label class="field">Kata sandi baru<input name="password" type="password" minlength="15" maxlength="256" required></label>
                <button class="button button-quiet" type="submit">Simpan kata sandi</button>
            </form>
            <?php if ($item['enabled']): ?>
            <form method="post" action="/admin/guru/<?= h($item['id']) ?>">
                <?php csrfField($csrf); ?><input type="hidden" name="action" value="disable">
                <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi nonaktifkan</label>
                <button class="button button-quiet" type="submit">Nonaktifkan guru</button>
            </form>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
