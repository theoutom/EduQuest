<header class="page-header"><h1>Kuis Guru</h1></header>
<section class="panel">
    <h2>Buat draf kuis</h2>
    <?php if (!$classes): ?><p>Admin perlu menugaskan kelas kepada Anda terlebih dahulu.</p><?php else: ?>
    <?php $values = []; ?>
    <form class="admin-form" method="post" action="/guru/kuis">
        <?php csrfField($csrf); ?>
        <input type="hidden" name="action" value="create">
        <label class="field">Kode kuis<input name="slug" maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*" required></label>
        <?php require __DIR__ . '/admin-quiz-fields.php'; ?>
        <?php require __DIR__ . '/admin-quiz-time.php'; ?>
        <label class="field">Kelas<select name="class_id" required>
            <?php foreach ($classes as $class): ?><option value="<?= h($class['id']) ?>"><?= h($class['name']) ?> · <?= h($class['academic_year']) ?></option><?php endforeach; ?>
        </select></label>
        <button class="button button-primary" type="submit">Buat draf</button>
    </form>
    <?php endif; ?>
</section>
<section class="panel">
    <h2>Kuis saya</h2>
    <ul class="admin-list admin-links">
        <?php foreach ($items as $item): ?><li><a href="/guru/kuis/<?= h($item['id']) ?>"><?= h($item['title']) ?></a></li><?php endforeach; ?>
    </ul>
</section>
