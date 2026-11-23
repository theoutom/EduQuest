<header class="page-header">
    <h1>Halo, <?= h($student['name']) ?>!</h1>
    <p class="muted">Pilih latihan atau lanjutkan sesi kuismu.</p>
</header>
<?php if ($ongoing): ?>
    <section class="panel dashboard-sessions">
        <h2>Sesi yang masih bisa dilanjutkan</h2>
        <ul class="history">
            <?php foreach ($ongoing as $session): ?>
            <li><span><?= h($session['title']) ?></span><a href="/sesi/<?= h($session['id']) ?>">Lanjutkan sesi</a></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
<div class="dashboard-row">
    <?php if ($featured): ?>
    <section class="study-banner" aria-labelledby="featured-title">
        <div class="banner-content">
            <p class="banner-context"><?= h($featured['topic_name']) ?></p>
            <h2 id="featured-title"><?= h($featured['title']) ?></h2>
            <p><?= h($featured['summary']) ?></p>
            <?php if ($featured['active_id'] !== null): ?>
                <a class="button button-inverse" href="/sesi/<?= h($featured['active_id']) ?>">Lanjutkan sesi</a>
            <?php else: ?>
                <a class="button button-inverse" href="<?= h(quizLink($featured)) ?>">Lihat kuis</a>
            <?php endif; ?>
        </div>
    </section>
    <?php else: ?>
    <section class="panel"><h2>Latihan kuis</h2><p class="muted">Belum ada kuis yang tersedia.</p></section>
    <?php endif; ?>
    <section class="panel dashboard-stats">
        <h2>Progres belajar</h2>
        <?php
        $hours = intdiv($progress['seconds'], 3600);
        $remaining = $progress['seconds'] % 3600;
        $duration = trim(($hours ? "$hours jam " : '') . ($remaining || !$hours ? formatDuration($remaining) : ''));
        ?>
        <dl>
            <div><dt>Kuis selesai</dt><dd><?= h($progress['completed']) ?></dd></div>
            <div><dt>Rata-rata nilai</dt><dd><?= $progress['average'] === null ? '—' : h($progress['average']) . '/100' ?></dd></div>
            <div><dt>Durasi kuis selesai</dt><dd><?= h($duration) ?></dd></div>
        </dl>
        <a class="button button-quiet" href="/profil">Lihat riwayat</a>
    </section>
</div>
<div class="dashboard-row dashboard-activity">
    <section class="panel" aria-labelledby="activity-title">
        <h2 id="activity-title">Aktivitas 7 hari</h2>
        <p class="muted">Percobaan kuis selesai per hari dalam WIB</p>
        <?php
        $maximum = max(1, ...array_column($activity, 'completed'));
        $days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        ?>
        <ul class="activity-list">
            <?php foreach ($activity as $row): $day = new DateTimeImmutable($row['day']); ?>
            <li>
                <time datetime="<?= h($row['day']) ?>"><?= h($days[(int) $day->format('w')] . ' ' . $day->format('d/m')) ?></time>
                <span><?= h($row['completed']) ?> percobaan</span>
                <meter aria-hidden="true" min="0" max="<?= h($maximum) ?>" value="<?= h($row['completed']) ?>"></meter>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="panel dashboard-mentor">
        <h2>Sesi mentor berikutnya</h2>
        <?php if ($mentor !== null): ?>
            <p><?= h($mentor['mentor_name']) ?></p>
            <p class="muted"><?= h(localDate($mentor['starts_at'])) ?></p>
            <a class="button button-quiet" href="/mentor/sesi">Buka sesi saya</a>
        <?php else: ?>
            <p class="muted">Belum ada sesi terkonfirmasi.</p>
            <a class="button button-quiet" href="/mentor">Cari mentor</a>
        <?php endif; ?>
    </section>
</div>
<section class="dashboard-catalog" aria-labelledby="dashboard-catalog-title">
    <header class="section-heading">
        <h2 id="dashboard-catalog-title">Kuis yang bisa kamu kerjakan</h2>
        <a class="button button-quiet" href="/kuis">Buka daftar kuis</a>
    </header>
    <?php if ($items === []): ?><p class="panel muted">Belum ada kuis yang bisa dibuka.</p><?php endif; ?>
    <div class="course-grid">
        <?php foreach ($items as $item): ?>
            <article class="panel course-card">
                <p class="muted"><?= h($item['topic_name']) ?></p>
                <h3><a href="<?= h(quizLink($item)) ?>"><?= h($item['title']) ?></a></h3>
                <p><?= h($item['total']) ?> soal <span class="meta-separator"></span> <?= h(formatDuration($item['duration_seconds'])) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
