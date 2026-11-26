<a class="breadcrumb" href="<?= $teacher ? '/guru' : '/admin' ?>">Kembali</a>
<header class="page-header"><h1>Kelola Quest</h1><p>Jadwalkan quest kuis atau pembahasan. Waktu menggunakan WIB.</p></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <section class="panel">
        <h2>Buat quest</h2>
        <?php if (!$quizzes || ($teacher && !$classes)): ?>
            <p>Belum ada kuis atau kelas yang dapat digunakan. Terbitkan kuis dan pastikan kelas sudah ditugaskan.</p>
        <?php else: ?>
        <form class="admin-form" method="post" action="<?= h($base) ?>">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="action" value="create">
            <label class="field">Judul<input name="title" maxlength="120" required value="<?= h(formValue($submitted, 'title')) ?>"></label>
            <label class="field">Kuis<select name="quiz_id" required><option value="">Pilih kuis</option><?php foreach ($quizzes as $quiz): ?><option value="<?= h($quiz['id']) ?>"><?= h($quiz['title']) ?></option><?php endforeach; ?></select></label>
            <label class="field">Kelas<select name="class_id" required><?php if (!$teacher): ?><option value="0">Semua kelas</option><?php else: ?><option value="">Pilih kelas</option><?php endif; ?><?php foreach ($classes as $class): if (!empty($class['archived_at'])) continue; ?><option value="<?= h($class['id']) ?>"><?= h($class['name']) ?> · <?= h($class['academic_year']) ?></option><?php endforeach; ?></select></label>
            <label class="field">Mulai (WIB)<input type="datetime-local" name="starts_at" required value="<?= h(formValue($submitted, 'starts_at')) ?>"></label>
            <label class="field">Berakhir (WIB)<input type="datetime-local" name="ends_at" required value="<?= h(formValue($submitted, 'ends_at')) ?>"></label>
            <label class="field">Jenis quest<select name="kind"><option value="quiz_finish">Selesaikan kuis</option><option value="review">Konfirmasi membaca pembahasan</option><option value="answers">Jawab soal dalam kuis selesai</option></select></label>
            <label class="field">Target (percobaan atau soal)<input type="number" name="target" min="1" max="100" required value="<?= h(formValue($submitted, 'target')) ?>"></label>
            <label class="field">Hadiah XP<input type="number" name="xp" min="1" max="1000" required value="<?= h(formValue($submitted, 'xp')) ?>"></label>
            <button class="button button-primary" type="submit">Terbitkan quest</button>
        </form>
        <?php endif; ?>
    </section>
    <section class="panel"><h2>Quest terjadwal</h2>
        <?php if (!$items): ?><p>Belum ada quest.</p><?php endif; ?>
        <ul class="admin-list"><?php foreach ($items as $item): ?><li class="admin-item"><strong><?= h($item['title']) ?></strong><p><?= h(localDate($item['starts_at'])) ?> sampai <?= h(localDate($item['ends_at'])) ?></p><p>Target <?= h($item['target']) ?> · <?= h($item['xp']) ?> XP</p>
            <?php if ($item['archived_at'] !== null): ?><p>Dihentikan</p>
            <?php elseif (!$teacher || (int) $item['created_by'] === $actor): ?>
                <form method="post" action="<?= h($base) ?>"><?php csrfField($csrf); ?><input type="hidden" name="action" value="archive"><input type="hidden" name="quest_id" value="<?= h($item['id']) ?>"><button class="button button-quiet" type="submit">Hentikan quest</button></form>
            <?php endif; ?>
        </li><?php endforeach; ?></ul>
    </section>
</div>
