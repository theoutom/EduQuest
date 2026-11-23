<a class="breadcrumb" href="/kuis">Daftar Kuis</a>
<header class="page-header">
    <h1><?= h($item['title']) ?></h1>
    <p class="muted"><?= h($item['summary']) ?></p>
</header>
<?php if ($item['archived_at'] !== null): ?>
    <p class="notice admin-feedback">Kuis diarsipkan. Kamu masih bisa membuka riwayat dan menyelesaikan sesi yang aktif.</p>
<?php endif; ?>
<div class="detail-grid">
    <section class="panel" aria-labelledby="overview-heading">
        <div class="tag-row"><span class="tag"><?= h($item['topic_name']) ?></span><span class="tag tag-neutral"><?= h($levels[$item['level']]) ?></span></div>
        <h2 id="overview-heading">Sebelum mulai</h2>
        <ol class="instructions">
            <li>Pilih satu jawaban untuk setiap soal. Kamu boleh menandai soal yang ingin diperiksa lagi.</li>
            <li>Waktu berjalan untuk seluruh sesi, termasuk saat kamu meninggalkan halaman.</li>
            <li>Kuis dinilai dari jawaban yang sudah tersimpan. Kunci dan pembahasan tersedia setelah sesi berakhir.</li>
        </ol>
        <h3>Riwayat percobaan</h3>
        <?php if ($item['history'] === []): ?>
            <p class="muted">Belum ada percobaan.</p>
        <?php else: ?>
            <ul class="history">
            <?php foreach ($item['history'] as $index => $past): ?>
                <li>
                    <span>Percobaan <?= count($item['history']) - $index ?></span>
                    <?php if ($past['score'] !== null): ?>
                        <a href="/hasil/<?= h($past['id']) ?>">Lihat hasil · <?= h($past['score']) ?>/100</a>
                    <?php elseif ($past['expired']): ?>
                        <a href="/hasil/<?= h($past['id']) ?>">Lihat hasil</a>
                    <?php else: ?>
                        <a href="/sesi/<?= h($past['id']) ?>">Lanjutkan sesi</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <aside class="panel quiz-facts" aria-labelledby="facts-heading">
        <h2 id="facts-heading">Ringkasan kuis</h2>
        <dl class="facts">
            <div><dt>Mode</dt><dd><?= !publicDemo() && $item['tutor_enabled'] ? 'Latihan dengan Tutor' : 'Mandiri' ?></dd></div>
            <div><dt>Jumlah soal</dt><dd><?= h($item['total']) ?></dd></div>
            <div><dt>Durasi</dt><dd><?= h(formatDuration($item['duration_seconds'])) ?></dd></div>
            <div><dt>Nilai lulus</dt><dd><?= h($item['passing_score']) ?>/100</dd></div>
            <div><dt>Sisa percobaan baru</dt><dd><?= max(0, $item['attempt_limit'] - $item['used']) ?></dd></div>
        </dl>
        <?php if ($item['active_id'] !== null): ?>
            <a class="button button-primary w-full" href="/sesi/<?= h($item['active_id']) ?>">Lanjutkan sesi</a>
        <?php elseif ($item['archived_at'] === null && $item['used'] < $item['attempt_limit']): ?>
            <form method="post" action="<?= h(quizLink($item)) ?>/mulai">
                <?php csrfField($csrf); ?>
                <button class="button button-primary w-full" type="submit">Mulai kuis</button>
            </form>
        <?php elseif ($item['archived_at'] === null): ?>
            <p class="notice">Batas percobaan sudah tercapai.</p>
        <?php endif; ?>
    </aside>
</div>
