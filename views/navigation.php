<?php $isAdmin = $student['role'] === 'admin'; ?>
<a class="brand" href="<?= $isAdmin ? '/admin' : '/' ?>" aria-label="EduQuest, dashboard">
    <span class="brand-symbol" aria-hidden="true"></span>
    <span>EduQuest</span>
</a>
<nav class="nav-links" aria-label="Navigasi utama">
    <?php foreach (($isAdmin ? [
        '/admin' => ['Administrasi', 'home'],
        '/admin/topik' => ['Topik', 'community'],
        '/admin/kuis' => ['Kuis dan Soal', 'quiz'],
        '/admin/siswa' => ['Akun Siswa', 'profile'],
        '/admin/komunitas' => ['Moderasi Komunitas', 'community'],
        '/admin/mentor' => ['Mentor', 'mentor'],
        '/admin/riwayat' => ['Riwayat', 'quiz'],
    ] : [
        '/' => ['Dashboard', 'home'],
        '/kuis' => ['Daftar Kuis', 'quiz'],
        '/komunitas' => ['Bank Soal Komunitas', 'community'],
        '/mentor' => ['Cari Mentor', 'mentor'],
        '/profil' => ['Profil', 'profile'],
    ]) as $href => [$label, $icon]): ?>
        <a href="<?= h($href) ?>" <?= navCurrent($path, $href) ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon icon-<?= h($icon) ?>" aria-hidden="true"></span><?= h($label) ?>
        </a>
    <?php endforeach; ?>
</nav>
<form class="logout" method="post" action="/keluar">
    <?php csrfField($csrf); ?>
    <button class="logout-button" type="submit">Keluar</button>
</form>
