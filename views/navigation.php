<?php $isAdmin = $student['role'] === 'admin'; $isTeacher = $student['role'] === 'teacher'; ?>
<a class="brand" href="<?= $isAdmin ? '/admin' : ($isTeacher ? '/guru' : '/') ?>" aria-label="EduQuest, dashboard">
    <span class="brand-symbol" aria-hidden="true"></span>
    <span>EduQuest</span>
</a>
<nav class="nav-links" aria-label="Navigasi utama">
    <?php foreach (($isAdmin ? [
        '/admin' => ['Administrasi', 'home'],
        '/admin/topik' => ['Topik', 'community'],
        '/admin/kuis' => ['Kuis dan Soal', 'quiz'],
        '/admin/kelas' => ['Kelas', 'community'],
        '/admin/guru' => ['Guru', 'profile'],
        '/admin/siswa' => ['Akun Siswa', 'profile'],
        '/admin/komunitas' => ['Moderasi Komunitas', 'community'],
        '/admin/mentor' => ['Mentor', 'mentor'],
        '/admin/riwayat' => ['Riwayat', 'quiz'],
    ] : ($isTeacher ? [
        '/guru' => ['Ruang Guru', 'home'],
        '/guru/kuis' => ['Kuis Saya', 'quiz'],
        '/guru/minigame' => ['Susun Langkah', 'quiz'],
        '/guru/mastery' => ['Mastery Tree', 'quiz'],
    ] : [
        '/' => ['Dashboard', 'home'],
        '/kuis' => ['Daftar Kuis', 'quiz'],
        '/minigame' => ['Susun Langkah', 'quiz'],
        '/arena' => ['Arena Kuis', 'quiz'],
        '/mastery' => ['Mastery Tree', 'quiz'],
        '/jadwal' => ['Jadwal Ujian', 'quiz'],
        '/komunitas' => ['Bank Soal Komunitas', 'community'],
        '/mentor' => ['Cari Mentor', 'mentor'],
        '/profil' => ['Profil', 'profile'],
    ])) as $href => [$label, $icon]): ?>
        <?php if (publicDemo() && in_array($href, ['/jadwal', '/komunitas', '/mentor', '/minigame', '/arena', '/mastery'], true)) continue; ?>
        <a href="<?= h($href) ?>" <?= navCurrent($path, $href) ? 'aria-current="page"' : '' ?>>
            <span class="nav-icon icon-<?= h($icon) ?>" aria-hidden="true"></span><?= h($label) ?>
        </a>
    <?php endforeach; ?>
</nav>
<form class="logout" method="post" action="/keluar">
    <?php csrfField($csrf); ?>
    <button class="logout-button" type="submit">Keluar</button>
</form>
