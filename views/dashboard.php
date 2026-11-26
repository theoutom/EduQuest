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
            <div><dt>Streak belajar</dt><dd><?= h($streak) ?> hari</dd></div>
        </dl>
        <a class="button button-quiet" href="/profil">Lihat riwayat</a>
    </section>
</div>
<section class="panel dashboard-activity" aria-labelledby="quest-title" id="quest-list">
    <h2 id="quest-title">Quest aktif</h2>
    <p>Total XP: <strong><?= h($questXp + $minigameXp) ?> XP</strong></p>
    <p>Total XP quest: <strong><?= h($questXp) ?> XP</strong></p>
    <?php if (!publicDemo()): ?><p>XP permainan: <strong><?= h($minigameXp) ?> XP</strong> · <a href="/minigame">Main Susun Langkah</a></p><?php endif; ?>
    <?php if (!$quests): ?>
        <p class="muted">Belum ada quest aktif untukmu.</p>
    <?php else: ?>
        <ul class="history">
        <?php foreach ($quests as $quest): ?>
            <li>
                <div>
                    <strong><?= h($quest['title']) ?></strong>
                    <p><?= $quest['kind'] === 'review' ? 'Baca dan konfirmasi pembahasan' : ($quest['kind'] === 'answers' ? 'Jawab soal dan kumpulkan kuis' : 'Selesaikan kuis') ?></p>
                    <p>Progres <?= h(min((int) $quest['progress'], (int) $quest['target'])) ?>/<?= h($quest['target']) ?> · <?= h($quest['xp']) ?> XP</p>
                    <p><?= (int) $quest['awarded_xp'] > 0 ? 'Selesai' : 'Berakhir ' . h(localDate($quest['ends_at'])) ?></p>
                    <?php if (!$quest['target_reachable']): ?>
                        <p>Aktivitas yang tersisa tidak cukup untuk mencapai target. Minta guru memeriksa quest.</p>
                    <?php endif; ?>
                </div>
                <?php if ($quest['kind'] === 'review' && $quest['result_id'] !== null): ?>
                    <a href="/hasil/<?= h($quest['result_id']) ?>">Buka pembahasan</a>
                <?php else: ?>
                    <a href="/kuis/<?= h($quest['quiz_slug']) ?>">Lihat kuis</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<section class="panel dashboard-activity" aria-labelledby="daily-title">
    <h2 id="daily-title">Tantangan harian</h2>
    <?php if ($challenge === null): ?>
        <p class="muted">Belum ada tantangan untuk hari ini.</p>
    <?php else: ?>
        <p><?= h($challenge['title']) ?> · <?= h(formatDuration($challenge['duration_seconds'])) ?></p>
        <?php if ($challenge['completed_id'] !== null): ?>
            <p><strong>Selesai hari ini</strong></p>
            <a class="button button-quiet tutor-trigger" href="/hasil/<?= h($challenge['completed_id']) ?>">Lihat hasil</a>
        <?php elseif ($challenge['active_id'] !== null): ?>
            <a class="button button-primary tutor-trigger" href="/sesi/<?= h($challenge['active_id']) ?>">Lanjutkan tantangan</a>
        <?php elseif ($challenge['used'] >= $challenge['attempt_limit']): ?>
            <p class="muted">Jatah percobaan kuis ini sudah habis.</p>
        <?php else: ?>
            <a class="button button-quiet tutor-trigger" href="<?= h(quizLink($challenge)) ?>">Lihat tantangan</a>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php if (!publicDemo()): ?>
<section class="panel dashboard-activity" aria-labelledby="exams-title">
    <header class="section-heading">
        <h2 id="exams-title">Jadwal ujian</h2>
        <a class="button button-quiet" href="/jadwal">Lihat semua jadwal</a>
    </header>
    <?php require __DIR__ . '/exam-list.php'; ?>
</section>
<?php endif; ?>
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
    <?php if (!publicDemo()): ?><section class="panel dashboard-mentor">
        <h2>Sesi mentor berikutnya</h2>
        <?php if ($mentor !== null): ?>
            <p><?= h($mentor['mentor_name']) ?></p>
            <p class="muted"><?= h(localDate($mentor['starts_at'])) ?></p>
            <a class="button button-quiet" href="/mentor/sesi">Buka sesi saya</a>
        <?php else: ?>
            <p class="muted">Belum ada sesi terkonfirmasi.</p>
            <a class="button button-quiet" href="/mentor">Cari mentor</a>
        <?php endif; ?>
    </section><?php endif; ?>
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
