<header class="page-header"><h1>Kelola Siswa</h1><p class="muted">Buat akun siswa dan kelola akses ke ruang belajar.</p></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <section class="panel" aria-labelledby="new-student">
        <h2 id="new-student">Tambah akun siswa</h2>
        <form class="admin-form" method="post" action="/admin/siswa">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="create">
            <label class="field">Nama siswa<input name="name" maxlength="80" value="<?= h(formValue($submitted, 'name')) ?>" required></label>
            <label class="field">Nama pengguna<input name="username" autocapitalize="none" minlength="3" maxlength="48" value="<?= h(formValue($submitted, 'username')) ?>" autocomplete="off" required></label>
            <label class="field">Kata sandi awal<input name="password" type="password" minlength="15" maxlength="256" autocomplete="new-password" required></label>
            <p class="muted">Gunakan minimal 15 karakter.</p>
            <button class="button button-primary" type="submit">Buat akun siswa</button>
        </form>
    </section>
    <section class="panel" aria-labelledby="student-list">
        <h2 id="student-list">Daftar siswa</h2>
        <?php if (!$items): ?><p class="muted">Belum ada akun siswa.</p><?php endif; ?>
        <ul class="admin-list admin-links">
            <?php foreach ($items as $item): ?>
            <li>
                <a href="/admin/siswa/<?= h($item['id']) ?>"><?= h($item['name']) ?></a>
                <p class="muted"><?= h($item['username']) ?></p>
                <span class="tag <?= $item['enabled'] ? 'tag-success' : 'tag-neutral' ?>"><?= $item['enabled'] ? 'Aktif' : 'Nonaktif' ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
