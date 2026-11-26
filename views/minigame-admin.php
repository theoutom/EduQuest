<a class="breadcrumb" href="<?= $teacher ? '/guru' : '/admin' ?>">Kembali</a>
<header class="page-header"><h1>Kelola Susun Langkah</h1><p>Buat permainan dengan langkah yang ditulis dalam urutan benar.</p></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid"><section class="panel"><h2>Permainan baru</h2>
    <?php if ($teacher && !$classes): ?><p>Belum ada kelas yang ditugaskan.</p><?php else: ?>
    <form class="admin-form" method="post" action="<?= h($base) ?>">
        <?php csrfField($csrf); ?><input type="hidden" name="action" value="create">
        <label class="field">Judul<input name="title" maxlength="120" required value="<?= h(formValue($submitted, 'title')) ?>"></label>
        <label class="field">Instruksi<textarea name="instruction" maxlength="2000" required><?= h(formValue($submitted, 'instruction')) ?></textarea></label>
        <label class="field">Langkah dalam urutan benar, satu per baris (2–8 langkah)<textarea name="steps" rows="6" required><?= h(formValue($submitted, 'steps')) ?></textarea></label>
        <label class="field">Pembahasan<textarea name="explanation" maxlength="4000" required><?= h(formValue($submitted, 'explanation')) ?></textarea></label>
        <label class="field">Kelas<select name="class_id" required><?php if (!$teacher): ?><option value="0">Semua kelas</option><?php else: ?><option value="">Pilih kelas</option><?php endif; ?><?php foreach ($classes as $class): if (!empty($class['archived_at'])) continue; ?><option value="<?= h($class['id']) ?>"><?= h($class['name']) ?> · <?= h($class['academic_year']) ?></option><?php endforeach; ?></select></label>
        <label class="field">Hadiah XP untuk keberhasilan pertama<input type="number" name="xp" min="0" max="1000" required value="<?= h(formValue($submitted, 'xp', '0')) ?>"></label>
        <button class="button button-primary" type="submit">Terbitkan permainan</button>
    </form><?php endif; ?>
</section><section class="panel"><h2>Permainan</h2>
    <?php if (!$items): ?><p>Belum ada permainan.</p><?php endif; ?>
    <ul class="admin-list"><?php foreach ($items as $item): ?><li class="admin-item"><strong><?= h($item['title']) ?></strong><p><?= h($item['xp']) ?> XP</p>
        <?php if ($item['archived_at'] !== null): ?><p>Dihentikan</p><?php else: ?><form method="post" action="<?= h($base) ?>"><?php csrfField($csrf); ?><input type="hidden" name="action" value="archive"><input type="hidden" name="game_id" value="<?= h($item['id']) ?>"><button class="button button-quiet" type="submit">Hentikan permainan</button></form><?php endif; ?>
    </li><?php endforeach; ?></ul>
</section></div>
