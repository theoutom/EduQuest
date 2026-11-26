<header class="page-header"><h1>Arena Kuis</h1><p>Undang teman sekelas untuk pertandingan persahabatan.</p></header>
<?php if ($message !== ''): ?><p class="panel" role="alert"><?= h($message) ?></p><?php endif; ?>
<div class="dashboard-row">
<section class="panel"><h2>Undang teman</h2>
<?php if (!$lobby['classes'] || !$lobby['peers'] || !$lobby['quizzes']): ?>
    <p>Anda memerlukan kelas aktif, teman sekelas, dan kuis terbit untuk bertanding.</p>
<?php else: ?>
    <form method="post" action="/arena" class="admin-form">
        <?php csrfField($csrf); ?><input type="hidden" name="action" value="invite">
        <label class="field">Kelas<select name="class_id" required><option value="">Pilih kelas</option><?php foreach ($lobby['classes'] as $class): ?><option value="<?= h($class['id']) ?>"><?= h($class['name']) ?> · <?= h($class['academic_year']) ?></option><?php endforeach; ?></select></label>
        <label class="field">Teman<select name="opponent" required><option value="">Pilih teman</option><?php foreach ($lobby['peers'] as $peer): ?><option value="<?= h($peer['id']) ?>"><?= h($peer['name']) ?> · <?= h($peer['class_name']) ?></option><?php endforeach; ?></select></label>
        <label class="field">Kuis<select name="quiz_id" required><option value="">Pilih kuis</option><?php foreach ($lobby['quizzes'] as $quiz): ?><option value="<?= h($quiz['id']) ?>"><?= h($quiz['title']) ?></option><?php endforeach; ?></select></label>
        <button class="button button-primary" type="submit">Kirim undangan</button>
    </form>
<?php endif; ?>
<p>Undangan berlaku 5 menit. Keduanya mendapat soal dan tenggat yang sama. Nilai akademik tidak berubah.</p>
</section>
<section class="panel"><h2>Pertandingan saya</h2>
    <?php if (!$lobby['matches']): ?><p>Belum ada pertandingan.</p><?php endif; ?>
    <ul class="admin-list"><?php foreach ($lobby['matches'] as $game): ?><li><a href="/arena/<?= h($game['id']) ?>"><?= h($game['title']) ?> · <?= h($game['opponent']) ?></a></li><?php endforeach; ?></ul>
</section>
</div>
