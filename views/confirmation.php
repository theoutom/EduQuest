<header class="page-header"><h1>Kumpulkan kuis?</h1><p class="muted"><?= h($attempt['title']) ?></p></header>
<section class="panel confirmation">
    <p><?= $blank ?> soal belum dijawab. Setelah dikumpulkan, jawaban tidak bisa diubah.</p>
    <div class="dialog-actions">
        <a class="button button-quiet" href="<?= h($base) ?>?soal=<?= $position ?>">Kembali ke soal</a>
        <form method="post" action="<?= h($base) ?>/kumpulkan">
            <?php csrfField($csrf); ?>
            <button class="button button-primary" type="submit">Ya, kumpulkan</button>
        </form>
    </div>
</section>
