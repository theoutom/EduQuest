<header class="page-header">
    <h1>Daftar Kuis</h1>
    <p class="muted">Temukan latihan berdasarkan judul, topik, atau tingkat kesulitan.</p>
</header>
<form class="panel filters" method="get" action="/kuis">
    <label class="field search-field">Cari judul
        <input name="q" type="search" value="<?= h($filters['q']) ?>" placeholder="Contoh: struktur data" maxlength="120">
    </label>
    <label class="field">Topik
        <select name="topik">
            <option value="">Semua topik</option>
            <?php foreach ($topics as $topic): ?>
                <option value="<?= h($topic['slug']) ?>" <?= $filters['topik'] === $topic['slug'] ? 'selected' : '' ?>><?= h($topic['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">Tingkat
        <select name="level">
            <option value="">Semua tingkat</option>
            <?php foreach ($levels as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= $filters['level'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">Status
        <select name="status">
            <option value="">Semua status</option>
            <?php foreach ($states as $value => $label): ?>
                <?php if (publicDemo() && $value === 'tersimpan') continue; ?>
                <option value="<?= h($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="button button-primary" type="submit">Tampilkan kuis</button>
</form>
<p class="list-count muted"><?= count($items) ?> kuis</p>
<?php if ($items === []): ?>
    <section class="panel empty-state">
        <h2>Kuis tidak ditemukan</h2>
        <p class="muted">Coba kata kunci lain atau kosongkan filter.</p>
        <a class="button button-quiet" href="/kuis">Hapus filter</a>
    </section>
<?php endif; ?>
<div class="quiz-list">
    <?php foreach ($items as $item): ?>
        <article class="panel quiz-card">
            <div class="topic-tile" aria-hidden="true"><span></span></div>
            <div class="quiz-info">
                <div class="tag-row">
                    <span class="tag"><?= h($item['topic_name']) ?></span>
                    <span class="tag tag-neutral"><?= h($levels[$item['level']]) ?></span>
                    <span class="tag <?= $item['state'] === 'selesai' ? 'tag-success' : 'tag-neutral' ?>"><?= h($states[$item['state']]) ?></span>
                </div>
                <h2><?= h($item['title']) ?></h2>
                <p class="muted"><?= h($item['summary']) ?></p>
                <p class="meta"><?= h($item['total']) ?> soal <span class="meta-separator"></span> <?= h(formatDuration($item['duration_seconds'])) ?></p>
            </div>
            <div class="quiz-card-actions">
            <?php if ($item['active_id'] !== null): ?>
                <a class="button button-primary" href="/sesi/<?= h($item['active_id']) ?>">Lanjutkan sesi</a>
            <?php elseif ($item['used'] >= $item['attempt_limit'] && $item['last_result_id'] !== null): ?>
                <a class="button button-quiet" href="/hasil/<?= h($item['last_result_id']) ?>">Lihat hasil terakhir</a>
            <?php else: ?>
                <a class="button button-quiet" href="<?= h(quizLink($item)) ?>">Lihat kuis<span class="sr-only"> <?= h($item['title']) ?></span></a>
            <?php endif; ?>
                <?php if (!publicDemo()): ?><form method="post" action="<?= h(quizLink($item) . '/simpanan' . ($query === '' ? '' : '?' . $query)) ?>">
                    <?php csrfField($csrf); ?>
                    <button class="button button-quiet" name="saved" value="<?= $item['bookmarked'] ? '0' : '1' ?>" type="submit"><?= $item['bookmarked'] ? 'Hapus simpanan' : 'Simpan kuis' ?><span class="sr-only"> <?= h($item['title']) ?></span></button>
                </form><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
