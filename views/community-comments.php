<?php if (!$item['comments']): ?><p class="muted community-stem">Belum ada komentar.</p><?php endif; ?>
<ul class="admin-list community-comment-list">
    <?php foreach ($item['comments'] as $comment): ?>
        <li class="admin-item">
            <div class="section-heading"><strong><?= h($comment['author']) ?></strong><small class="muted"><?= h(localDate($comment['created_at'])) ?></small></div>
            <p class="community-text"><?= h($comment['body']) ?></p>
            <?php if ($comment['hidden_at'] !== null): ?>
                <span class="tag tag-neutral">Disembunyikan</span>
            <?php elseif ($moderator || $comment['author_id'] === $student['id']): ?>
                <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                    <?php csrfField($csrf); ?><input type="hidden" name="action" value="hide-comment">
                    <input type="hidden" name="comment_id" value="<?= h($comment['id']) ?>">
                    <button class="button button-quiet" type="submit"><?= $moderator ? 'Sembunyikan komentar' : 'Tarik komentarmu' ?></button>
                </form>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
<?php
$number = $item['comment_page']; $hasNext = $item['more_comments'];
$pagingPath = $endpoint; $pagingFilters = []; $pagingKey = 'komentar'; $pagingLabel = 'Halaman komentar';
require __DIR__ . '/community-pagination.php';
?>
