<?php $endpoint = '/admin/siswa/' . $item['id']; ?>
<a class="breadcrumb" href="/admin/siswa">Daftar siswa</a>
<header class="page-header"><h1><?= h($item['name']) ?></h1><p class="muted"><?= h($item['username']) ?> · <?= h($item['attempts']) ?> percobaan kuis · <?= h($item['mentor_sessions']) ?> pemesanan mentor</p></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <section class="panel" aria-labelledby="student-name">
        <h2 id="student-name">Informasi akun</h2>
        <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="update">
            <label class="field">Nama siswa<input name="name" maxlength="80" required value="<?= h(formValue($submitted, 'name', $item['name'])) ?>"></label>
            <button class="button button-primary" type="submit">Simpan nama</button>
        </form>
    </section>
    <section class="panel" aria-labelledby="student-access">
        <h2 id="student-access">Akses akun</h2>
        <p class="muted">Status <?= $item['enabled'] ? 'aktif' : 'nonaktif' ?>. Mengganti kata sandi akan mengakhiri sesi yang sedang terbuka.</p>
        <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="reset">
            <label class="field">Kata sandi baru<input name="password" type="password" minlength="15" maxlength="256" autocomplete="new-password" required></label>
            <button class="button button-quiet" type="submit"><?= $item['enabled'] ? 'Ganti kata sandi' : 'Aktifkan dengan kata sandi baru' ?></button>
        </form>
        <?php if ($item['enabled']): ?>
        <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="disable">
            <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi nonaktifkan akun</label>
            <button class="button button-danger" type="submit">Nonaktifkan akun</button>
        </form>
        <?php endif; ?>
        <?php if ($item['attempts'] === 0 && $item['mentor_sessions'] === 0): ?>
        <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="delete">
            <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi hapus permanen</label>
            <button class="button button-danger" type="submit">Hapus akun siswa</button>
        </form>
        <?php endif; ?>
    </section>
</div>
