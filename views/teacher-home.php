<header class="page-header"><h1>Ruang Guru</h1><p>Kelas yang ditugaskan kepada Anda.</p></header>
<a class="button button-primary" href="/guru/kuis">Kelola kuis saya</a>
<section class="panel">
    <h2>Kelas saya</h2>
    <?php if (!$classes): ?><p>Belum ada kelas yang ditugaskan.</p><?php endif; ?>
    <ul class="admin-list">
        <?php foreach ($classes as $class): ?>
        <li><?= h($class['name']) ?> · <?= h($class['academic_year']) ?></li>
        <?php endforeach; ?>
    </ul>
</section>
