<header class="page-header"><h1>Kelola Kelas</h1></header>
<section class="panel">
    <h2>Buat kelas</h2>
    <form class="admin-form" action="/admin/kelas" method="post">
        <?php csrfField($csrf); ?>
        <input type="hidden" name="action" value="create">
        <label class="field">Nama kelas<input name="name" maxlength="80" required></label>
        <label class="field">Tahun ajaran<input name="year" maxlength="30" required></label>
        <button class="button button-primary" type="submit">Buat kelas</button>
    </form>
</section>
<section class="panel">
    <h2>Daftar kelas</h2>
    <ul class="admin-list admin-links">
        <?php foreach ($items as $item): ?>
        <li><a href="/admin/kelas/<?= h($item['id']) ?>"><?= h($item['name']) ?> · <?= h($item['academic_year']) ?></a></li>
        <?php endforeach; ?>
    </ul>
</section>
