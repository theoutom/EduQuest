<a class="breadcrumb" href="<?= $print ? '/hasil/' . h($review['id']) : '/kuis/' . h($review['slug']) ?>"><?= $print ? 'Kembali ke pembahasan' : 'Pengantar kuis' ?></a>
<header class="page-header section-heading">
    <div><h1>Hasil &amp; Pembahasan</h1><p class="muted"><?= h($review['title']) ?></p></div>
    <?php if ($print): ?>
        <button class="button button-primary review-print" data-print-review type="button" hidden>Cetak / simpan PDF</button>
        <noscript><p class="review-print muted">Buka menu cetak browser untuk mencetak atau menyimpan PDF.</p></noscript>
    <?php else: ?>
        <a class="button button-quiet review-print" href="/hasil/<?= h($review['id']) ?>?cetak=1">Siapkan cetak</a>
    <?php endif; ?>
</header>
<section class="panel score-panel" aria-label="Ringkasan nilai">
    <div class="score-display">
        <p>Nilai akhir</p>
        <p class="score"><strong><?= h($review['score']) ?></strong><span>/100</span></p>
        <span class="tag <?= $review['passed'] ? 'tag-success' : 'tag-warning' ?>"><?= $review['passed'] ? 'Lulus' : 'Belum lulus' ?></span>
    </div>
    <dl class="score-facts">
        <div><dt>Benar</dt><dd><?= h($review['correct']) ?></dd></div>
        <div><dt>Salah</dt><dd><?= h($review['wrong']) ?></dd></div>
        <div><dt>Kosong</dt><dd><?= h($review['blank']) ?></dd></div>
        <div><dt>Waktu pengerjaan</dt><dd><?= h(formatDuration($review['duration'])) ?></dd></div>
    </dl>
</section>
<section class="panel review-section" aria-labelledby="accuracy-title">
    <h2 id="accuracy-title">Ketepatan topik pada percobaan ini</h2>
    <p><?= h($review['topic_name']) ?> · <?= h($review['correct']) ?> dari <?= count($review['questions']) ?> soal benar</p>
    <meter class="review-meter" min="0" max="<?= count($review['questions']) ?>" value="<?= h($review['correct']) ?>" aria-hidden="true"></meter>
</section>
<section class="review-section" aria-labelledby="review-heading">
    <h2 id="review-heading">Pembahasan per soal</h2>
    <?php if (!$print): ?><nav class="filter-tabs" aria-label="Filter pembahasan">
        <?php foreach ($labels as $value => $label): ?>
            <a class="button button-quiet" href="/hasil/<?= h($review['id']) ?>?status=<?= h($value) ?>" <?= $value === $filter ? 'aria-current="page"' : '' ?>><?= h($label) ?></a>
        <?php endforeach; ?>
    </nav><?php endif; ?>
    <?php $shown = array_filter($review['questions'], static fn (array $question): bool => $filter === 'semua' || $question['outcome'] === $filter); ?>
    <?php if ($shown === []): ?><p class="panel muted">Tidak ada soal dengan status ini.</p><?php endif; ?>
    <div class="review-list">
    <?php foreach ($shown as $question): ?>
        <?php $tone = ['benar' => 'success', 'salah' => 'error', 'kosong' => 'neutral'][$question['outcome']]; ?>
        <article class="panel review-card">
            <div class="section-heading">
                <h3>Soal <?= h($question['position']) ?></h3>
                <span class="tag tag-<?= $tone ?>"><?= h($labels[$question['outcome']]) ?></span>
            </div>
            <p class="review-stem"><?= h($question['stem']) ?></p>
            <?php if ($question['snippet'] !== null): ?><pre class="code-block" tabindex="0"><code><?= h($question['snippet']) ?></code></pre><?php endif; ?>
            <dl class="answer-comparison">
                <div class="answer-selected">
                    <dt>Jawabanmu</dt>
                    <dd><?= $question['selected'] === null ? 'Tidak dijawab' : h($question['selected_label'] . '. ' . $question['selected']) ?></dd>
                </div>
                <div class="answer-correct">
                    <dt>Kunci resmi</dt>
                    <dd><?= h($question['correct_label'] . '. ' . $question['correct']) ?></dd>
                </div>
            </dl>
            <p><?= h($question['explanation']) ?></p>
        </article>
    <?php endforeach; ?>
    </div>
</section>
<?php if (!$print && !publicDemo()): ?>
<section class="panel review-section">
    <h2>Selesai membaca pembahasan?</h2>
    <p>Konfirmasi ini mencatat progres untuk quest pembahasan yang sedang aktif.</p>
    <?php if ($reviewConfirmed): ?><p>Konfirmasi tersimpan.</p><?php endif; ?>
    <form method="post" action="/hasil/<?= h($review['id']) ?>/selesai-baca"><?php csrfField($csrf); ?><button class="button button-quiet" type="submit">Saya sudah membaca</button></form>
</section>
<?php endif; ?>
