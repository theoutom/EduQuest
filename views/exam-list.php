<?php if (!$exams): ?><p class="muted">Belum ada jadwal ujian.</p><?php endif; ?>
<ul class="admin-list">
    <?php foreach ($exams as $exam): ?>
        <li class="admin-item">
            <div class="section-heading">
                <strong><?= h($exam['title']) ?></strong>
                <?php if ($exam['ongoing']): ?><span class="tag tag-warning">Berlangsung</span><?php endif; ?>
            </div>
            <p class="muted">
                <time datetime="<?= h($exam['starts_at']) ?>"><?= h(localDate($exam['starts_at'])) ?></time>
                sampai <time datetime="<?= h($exam['ends_at']) ?>"><?= h(localDate($exam['ends_at'])) ?></time>
            </p>
            <?php if ($exam['location'] !== ''): ?><p><?= h($exam['location']) ?></p><?php endif; ?>
            <?php if ($exam['note'] !== ''): ?><p class="community-text"><?= h($exam['note']) ?></p><?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
