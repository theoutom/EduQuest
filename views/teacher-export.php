<a class="breadcrumb" href="/guru/kuis/<?= h($quiz['id']) ?>">Kembali ke editor kuis</a>
<header class="page-header">
    <h1><?= $kind === 'nilai' ? 'Daftar nilai' : 'Soal dan pembahasan' ?></h1>
    <p><?= h($quiz['title']) ?></p>
    <?php if ($kind === 'soal'): ?>
    <p><?= $quiz['draft'] ? 'Draf versi ' . h($quiz['draft']['version']) : 'Versi terbit ' . h($quiz['versions'][0]['version'] ?? '') ?></p>
    <?php endif; ?>
    <button class="button button-primary review-print" type="button" data-print-review hidden>Cetak atau simpan sebagai PDF</button>
    <noscript><p>Gunakan menu cetak browser untuk menyimpan sebagai PDF.</p></noscript>
</header>
<?php if ($kind === 'nilai'): ?>
<section class="panel" aria-label="Hasil siswa">
    <?php if (!$results): ?><p>Belum ada hasil.</p><?php else: ?>
    <div class="table-scroll teacher-export-table"><table class="admin-table">
        <thead><tr><th>Nama siswa</th><th>Nilai</th><th>Waktu selesai (UTC)</th></tr></thead>
        <tbody><?php foreach ($results as $result): ?>
            <tr><td><?= h($result['name']) ?></td><td><?= h($result['score']) ?>/100</td><td><?= h($result['finished_at']) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</section>
<?php else: ?>
<?php foreach ($quiz['questions'] as $index => $question): ?>
<section class="panel">
    <h2>Soal <?= h($index + 1) ?></h2>
    <p><?= nl2br(h($question['stem'])) ?></p>
    <?php if ($question['snippet'] !== null && $question['snippet'] !== ''): ?><pre class="code-block"><code><?= h($question['snippet']) ?></code></pre><?php endif; ?>
    <ul class="admin-list"><?php foreach ($question['options'] as $option): ?>
        <li><?= h(chr(64 + (int) $option['position'])) ?>. <?= h($option['label']) ?><?= $option['correct'] ? ' (Jawaban benar)' : '' ?></li>
    <?php endforeach; ?></ul>
    <p><strong>Pembahasan</strong> <?= nl2br(h($question['explanation'])) ?></p>
</section>
<?php endforeach; ?>
<?php if (!$quiz['questions']): ?><p>Belum ada soal.</p><?php endif; ?>
<?php endif; ?>
