<?php if ($number > 1 || $hasNext): ?>
<nav class="admin-shortcuts community-content" aria-label="<?= h($pagingLabel) ?>">
    <?php if ($number > 1): ?><a class="button button-quiet" href="<?= h($pagingPath . '?' . http_build_query($pagingFilters + [$pagingKey => $number - 1])) ?>">Sebelumnya</a><?php endif; ?>
    <?php if ($hasNext && $number < 10000): ?><a class="button button-quiet" href="<?= h($pagingPath . '?' . http_build_query($pagingFilters + [$pagingKey => $number + 1])) ?>">Berikutnya</a><?php endif; ?>
</nav>
<?php endif; ?>
