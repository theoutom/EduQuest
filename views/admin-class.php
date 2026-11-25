<a class="breadcrumb" href="/admin/kelas">Daftar kelas</a>
<header class="page-header"><h1><?= h($class['name']) ?></h1><p><?= h($class['academic_year']) ?></p></header>
<section class="panel">
    <?php if ($class['archived_at'] !== null): ?>
    <p>Diarsipkan</p>
    <form method="post" action="/admin/kelas/<?= h($class['id']) ?>">
        <?php csrfField($csrf); ?><input type="hidden" name="action" value="restore">
        <button class="button button-quiet" type="submit">Pulihkan kelas</button>
    </form>
    <?php else: ?>
    <form method="post" action="/admin/kelas/<?= h($class['id']) ?>">
        <?php csrfField($csrf); ?><input type="hidden" name="action" value="archive">
        <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi arsip kelas</label>
        <button class="button button-quiet" type="submit">Arsipkan kelas</button>
    </form>
    <?php endif; ?>
</section>
<section class="panel">
    <h2>Anggota siswa</h2>
    <?php if (!$students): ?><p>Belum ada akun siswa aktif.</p><?php endif; ?>
    <ul class="admin-list">
        <?php foreach ($students as $item): ?>
        <li>
            <span><?= h($item['name']) ?> · <?= h($item['username']) ?></span>
            <form method="post" action="/admin/kelas/<?= h($class['id']) ?>">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="student_id" value="<?= h($item['id']) ?>">
                <input type="hidden" name="action" value="<?= $item['assigned'] ? 'remove' : 'assign' ?>">
                <button class="button button-quiet" type="submit"><?= $item['assigned'] ? 'Keluarkan' : 'Tambahkan' ?></button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<section class="panel">
    <h2>Guru yang ditugaskan</h2>
    <?php if (!$teachers): ?><p>Belum ada akun guru aktif.</p><?php endif; ?>
    <ul class="admin-list">
        <?php foreach ($teachers as $teacher): ?>
        <li>
            <span><?= h($teacher['name']) ?> · <?= h($teacher['username']) ?></span>
            <form method="post" action="/admin/kelas/<?= h($class['id']) ?>">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="teacher_id" value="<?= h($teacher['id']) ?>">
                <input type="hidden" name="action" value="<?= $teacher['assigned'] ? 'remove-teacher' : 'assign-teacher' ?>">
                <button class="button button-quiet" type="submit"><?= $teacher['assigned'] ? 'Lepaskan' : 'Tugaskan' ?></button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
