<section class="panel empty-state">
    <h1><?= h($title) ?></h1>
    <p class="muted"><?= h($message) ?></p>
    <?php $home = !$student ? ['/masuk', 'Kembali ke halaman masuk']
        : ($student['role'] === 'admin' ? ['/admin', 'Kembali ke administrasi'] : ['/kuis', 'Kembali ke daftar kuis']); ?>
    <a class="button button-primary" href="<?= h($home[0]) ?>"><?= h($home[1]) ?></a>
</section>
