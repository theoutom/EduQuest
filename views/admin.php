<header class="page-header">
    <h1>Administrasi EduQuest</h1>
    <p class="muted">Ringkasan penggunaan kuis dan aktivitas keamanan.</p>
</header>
<nav class="admin-shortcuts" aria-label="Aksi administrasi">
    <a class="button button-primary" href="/admin/kuis">Kelola kuis dan soal</a>
    <a class="button button-quiet" href="/admin/topik">Kelola topik</a>
    <a class="button button-quiet" href="/admin/siswa">Kelola akun siswa</a>
    <a class="button button-quiet" href="/admin/komunitas">Moderasi komunitas</a>
    <a class="button button-quiet" href="/admin/mentor">Kelola mentor</a>
    <a class="button button-quiet" href="/admin/tantangan">Jadwalkan tantangan</a>
    <a class="button button-quiet" href="/admin/jadwal">Kelola jadwal ujian</a>
    <a class="button button-quiet" href="/admin/riwayat">Lihat riwayat</a>
</nav>
<div class="detail-grid">
    <section class="panel" aria-labelledby="history-title">
        <h2 id="history-title">Aktivitas keamanan</h2>
        <?php if ($events === []): ?>
            <p class="muted">Belum ada aktivitas yang tercatat.</p>
        <?php else: ?>
            <ul class="history admin-history">
            <?php foreach ($events as $record): ?>
                <?php
                $label = [
                    'admin_login' => 'Admin masuk',
                    'admin_logout' => 'Admin keluar',
                    'account_configured' => 'Konfigurasi akun diperbarui',
                    'account_disabled' => 'Akun dinonaktifkan',
                ][$record['event']];
                $when = (new DateTimeImmutable($record['happened_at']))->setTimezone(new DateTimeZone('Asia/Jakarta'));
                ?>
                <li><span><?= h($label) ?></span> <time datetime="<?= h($when->format(DATE_ATOM)) ?>"><?= h($when->format('d/m/Y H:i')) ?> WIB</time></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <aside class="panel dashboard-stats">
        <h2>Penggunaan kuis</h2>
        <dl>
            <div><dt>Siswa aktif</dt><dd><?= h($stats['active_students']) ?></dd></div>
            <div><dt>Kuis di katalog</dt><dd><?= h($stats['quizzes']) ?></dd></div>
            <div><dt>Sesi dikumpulkan</dt><dd><?= h($stats['completed']) ?></dd></div>
        </dl>
    </aside>
</div>
