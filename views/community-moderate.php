<a class="breadcrumb" href="/admin/komunitas?status=<?= h($item['status']) ?>">Moderasi komunitas</a>
<header class="page-header"><h1><?= h($item['title']) ?></h1><p class="muted">Oleh <?= h($item['author']) ?></p></header>
<div class="detail-grid">
    <section class="panel">
        <div class="tag-row"><span class="tag"><?= h($item['topic_name']) ?></span><span class="tag tag-neutral"><?= h(Community::LEVELS[$item['level']]) ?></span></div>
        <h2 class="community-stem"><?= h($item['stem']) ?></h2>
        <ul class="admin-list community-options">
            <?php foreach ($item['options'] as $letter => $label): ?>
                <li><?= strtoupper($letter) ?>. <?= h($label) ?> <?= $letter === $item['correct'] ? '<span class="tag tag-success">Kunci</span>' : '' ?></li>
            <?php endforeach; ?>
        </ul>
        <h3 class="community-stem">Pembahasan</h3><p class="community-text"><?= h($item['explanation']) ?></p>
    </section>
    <aside class="panel">
        <h2>Keputusan moderasi</h2>
        <p><?= h(Community::STATES[$item['status']]) ?></p>
        <?php if ($item['moderation_note']): ?><p class="community-text muted"><?= h($item['moderation_note']) ?></p><?php endif; ?>
        <?php if (in_array($item['status'], ['pending', 'archived'], true)): ?>
        <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
            <?php csrfField($csrf); ?><input type="hidden" name="action" value="<?= $item['status'] === 'pending' ? 'publish' : 'restore' ?>">
            <input type="hidden" name="revision" value="<?= h($item['revision']) ?>">
            <button class="button button-primary" type="submit"><?= $item['status'] === 'pending' ? 'Setujui dan terbitkan' : 'Pulihkan soal' ?></button>
        </form>
        <?php endif; ?>
        <?php if (in_array($item['status'], ['pending', 'published'], true)): ?>
        <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
            <?php csrfField($csrf); ?><input type="hidden" name="action" value="<?= $item['status'] === 'pending' ? 'reject' : 'archive' ?>">
            <input type="hidden" name="revision" value="<?= h($item['revision']) ?>">
            <label class="field">Alasan untuk kontributor<textarea name="reason" rows="3" maxlength="400" required></textarea></label>
            <button class="button button-danger" type="submit"><?= $item['status'] === 'pending' ? 'Minta revisi' : 'Arsipkan soal' ?></button>
        </form>
        <?php endif; ?>
    </aside>
</div>
<?php if (in_array($item['status'], ['published', 'archived'], true)): ?>
<section class="panel community-content"><h2>Diskusi</h2><?php $moderator = true; require __DIR__ . '/community-comments.php'; ?></section>
<?php endif; ?>
