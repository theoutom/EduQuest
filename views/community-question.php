<a class="breadcrumb" href="/komunitas">Bank soal komunitas</a>
<header class="page-header">
    <h1><?= h($item['title']) ?></h1>
    <p class="muted">Oleh <?= h($item['author']) ?></p>
</header>
<div class="detail-grid community-content">
    <div class="admin-stack">
        <section class="panel">
            <div class="tag-row">
                <span class="tag"><?= h($item['topic_name']) ?></span><span class="tag tag-neutral"><?= h(Community::LEVELS[$item['level']]) ?></span>
                <span class="tag <?= $item['status'] === 'published' ? 'tag-success' : 'tag-warning' ?>"><?= h(Community::STATES[$item['status']]) ?></span>
            </div>
            <h2 class="community-stem"><?= h($item['stem']) ?></h2>
            <?php if (isset($item['correct'])): ?>
                <?php if ($item['selected'] !== null): ?><p class="community-stem"><strong><?= $item['selected'] === $item['correct'] ? 'Jawabanmu benar.' : 'Jawabanmu belum tepat.' ?></strong></p><?php endif; ?>
                <ul class="admin-list community-options">
                    <?php foreach ($item['options'] as $letter => $label): ?>
                    <li><?= strtoupper($letter) ?>. <?= h($label) ?>
                        <?php if ($letter === $item['correct']): ?><span class="tag tag-success">Kunci</span><?php endif; ?>
                        <?php if ($letter === $item['selected']): ?><span class="tag tag-neutral">Jawabanmu</span><?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <h3 class="community-stem">Pembahasan</h3>
                <p class="community-text"><?= h($item['explanation']) ?></p>
            <?php else: ?>
                <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                    <?php csrfField($csrf); ?><input type="hidden" name="action" value="answer">
                    <fieldset class="choices"><legend>Pilih jawaban</legend>
                    <?php foreach ($item['options'] as $letter => $label): ?>
                        <label class="choice"><input type="radio" name="choice" value="<?= $letter ?>" required><span><?= strtoupper($letter) ?>. <?= h($label) ?></span></label>
                    <?php endforeach; ?>
                    </fieldset>
                    <button class="button button-primary" type="submit">Periksa jawaban</button>
                </form>
            <?php endif; ?>
            <?php if ($item['status'] === 'published'): ?>
                <?php $tutorEndpoint = $endpoint . '/tutor'; $tutorPosition = null; ?>
                <a class="button button-quiet tutor-trigger" data-tutor-open href="<?= h($tutorEndpoint) ?>">Tutor latihan</a>
            <?php endif; ?>
        </section>
        <?php if ($item['status'] === 'published'): ?>
        <section class="panel" aria-labelledby="discussion">
            <h2 id="discussion">Diskusi</h2>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?><input type="hidden" name="action" value="comment">
                <label class="field">Komentarmu<textarea name="body" rows="3" maxlength="1000" required></textarea></label>
                <button class="button button-primary" type="submit">Kirim komentar</button>
            </form>
            <?php $moderator = false; require __DIR__ . '/community-comments.php'; ?>
        </section>
        <?php endif; ?>
    </div>
    <aside class="panel">
        <?php if ($item['status'] === 'published'): ?>
            <h2><?= h($item['votes']) ?> suara</h2>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?><input type="hidden" name="action" value="<?= $item['voted'] ? 'unvote' : 'vote' ?>">
                <button class="button button-quiet" type="submit"><?= $item['voted'] ? 'Batalkan suara' : 'Beri suara' ?></button>
            </form>
        <?php else: ?>
            <h2>Status pengajuan</h2>
            <p><?= h(Community::STATES[$item['status']]) ?></p>
            <?php if (!empty($item['moderation_note'])): ?><p class="community-text muted"><?= h($item['moderation_note']) ?></p><?php endif; ?>
            <?php if ($item['owns'] && $item['status'] === 'pending'): ?><a class="button button-quiet" href="<?= h($endpoint) ?>?edit=1">Ubah pengajuan</a><?php endif; ?>
            <?php if ($item['owns'] && $item['status'] === 'rejected'): ?><a class="button button-quiet" href="/komunitas/buat?salin=<?= h($item['id']) ?>">Salin untuk revisi</a><?php endif; ?>
            <?php if ($item['owns'] && in_array($item['status'], ['pending', 'rejected'], true)): ?>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?><input type="hidden" name="action" value="delete">
                <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi hapus pengajuan</label>
                <button class="button button-danger" type="submit">Hapus pengajuan</button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </aside>
</div>
<?php if ($item['status'] === 'published'): require __DIR__ . '/tutor-panel.php'; endif; ?>
