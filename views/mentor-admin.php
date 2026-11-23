<header class="page-header"><h1>Kelola Mentor</h1><p class="muted">Kelola profil, jadwal, dan permintaan sesi siswa.</p></header>
<nav class="admin-shortcuts" aria-label="Administrasi mentor"><a class="button button-primary" href="/admin/mentor/sesi">Buka pemesanan</a></nav>
<div class="admin-grid">
    <section class="panel" aria-labelledby="mentor-new">
        <h2 id="mentor-new">Tambah mentor</h2>
        <form class="admin-form" method="post" action="/admin/mentor">
            <?php csrfField($csrf); ?><input type="hidden" name="action" value="create">
            <?php $values = []; require __DIR__ . '/mentor-fields.php'; ?>
            <button class="button button-primary" type="submit">Simpan mentor</button>
        </form>
    </section>
    <section class="panel">
        <h2>Daftar mentor</h2>
        <?php if (!$items): ?><p class="muted">Belum ada profil mentor.</p><?php endif; ?>
        <ul class="admin-list admin-links">
        <?php foreach ($items as $item): ?>
            <li><a href="/admin/mentor/<?= h($item['id']) ?>"><?= h($item['name']) ?></a>
                <p class="muted"><?= h(Mentor::CATEGORIES[$item['category']]) ?> · <?= h(mentorRate($item['rate'])) ?></p>
                <span class="tag <?= $item['enabled'] ? 'tag-success' : 'tag-neutral' ?>"><?= $item['enabled'] ? 'Terlihat di direktori' : 'Nonaktif' ?></span>
            </li>
        <?php endforeach; ?>
        </ul>
    </section>
</div>
