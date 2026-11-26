<?php
$isAdmin = $student['role'] === 'admin'; $isTeacher = $student['role'] === 'teacher';
// Static SVG fragments; labels remain visible and the icons are decorative.
$navIcons = [
    'home' => '<path d="m3 10 9-7 9 7v9a2 2 0 0 1-2 2h-5v-7h-4v7H5a2 2 0 0 1-2-2z"/>',
    'quiz' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>',
    'topics' => '<path d="M4 5h7l2 2h7v12H4z"/><path d="M4 9h16"/>',
    'classes' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4M7 8h6"/>',
    'teachers' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 7h5M18.5 4.5v5"/>',
    'students' => '<circle cx="8" cy="8" r="3"/><path d="M2 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M17 14a5 5 0 0 1 5 5v1"/>',
    'community' => '<path d="M4 4h16v12H9l-5 4z"/><path d="M8 8h8M8 12h5"/>',
    'mentor' => '<circle cx="12" cy="7" r="3"/><path d="M5 21v-2a7 7 0 0 1 14 0v2M3 4l2-2M21 4l-2-2"/>',
    'history' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'quest' => '<path d="M5 21V4m0 0c5-3 8 3 14 0v10c-6 3-9-3-14 0"/>',
    'steps' => '<path d="M4 18h5v-5h5V8h6M16 4l4 4-4 4"/>',
    'mastery' => '<path d="M12 21v-9M12 15 6 9M12 12l6-6"/><circle cx="6" cy="7" r="2"/><circle cx="18" cy="4" r="2"/><circle cx="12" cy="10" r="2"/>',
    'arena' => '<path d="m4 4 16 16M20 4 4 20M3 3l4 1-3 3zM21 3l-4 1 3 3zM3 17l4 4M17 21l4-4"/>',
    'schedule' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18M8 14h2M14 14h2M8 17h2"/>',
    'profile' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
];
?>
<a class="brand" href="<?= $isAdmin ? '/admin' : ($isTeacher ? '/guru' : '/') ?>" aria-label="EduQuest, dashboard">
    <span class="brand-symbol" aria-hidden="true"></span>
    <span>EduQuest</span>
</a>
<nav class="nav-links" aria-label="Navigasi utama">
    <?php foreach (($isAdmin ? [
        '/admin' => ['Administrasi', 'home'],
        '/admin/topik' => ['Topik', 'topics'],
        '/admin/kuis' => ['Kuis dan Soal', 'quiz'],
        '/admin/kelas' => ['Kelas', 'classes'],
        '/admin/guru' => ['Guru', 'teachers'],
        '/admin/siswa' => ['Akun Siswa', 'students'],
        '/admin/komunitas' => ['Moderasi Komunitas', 'community'],
        '/admin/mentor' => ['Mentor', 'mentor'],
        '/admin/riwayat' => ['Riwayat', 'history'],
    ] : ($isTeacher ? [
        '/guru' => ['Ruang Guru', 'home'],
        '/guru/kuis' => ['Kuis Saya', 'quiz'],
        '/guru/quest' => ['Quest Kelas', 'quest'],
        '/guru/minigame' => ['Susun Langkah', 'steps'],
        '/guru/mastery' => ['Mastery Tree', 'mastery'],
    ] : [
        '/' => ['Dashboard', 'home'],
        '/kuis' => ['Daftar Kuis', 'quiz'],
        '/minigame' => ['Susun Langkah', 'steps'],
        '/arena' => ['Arena Kuis', 'arena'],
        '/mastery' => ['Mastery Tree', 'mastery'],
        '/jadwal' => ['Jadwal Ujian', 'schedule'],
        '/komunitas' => ['Bank Soal Komunitas', 'community'],
        '/mentor' => ['Cari Mentor', 'mentor'],
        '/profil' => ['Profil', 'profile'],
    ])) as $href => [$label, $icon]): ?>
        <?php if (publicDemo() && in_array($href, ['/jadwal', '/komunitas', '/mentor', '/minigame', '/arena', '/mastery'], true)) continue; ?>
        <a href="<?= h($href) ?>" <?= navCurrent($path, $href) ? 'aria-current="page"' : '' ?>>
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $navIcons[$icon] ?></svg><?= h($label) ?>
        </a>
    <?php endforeach; ?>
</nav>
<form class="logout" method="post" action="/keluar">
    <?php csrfField($csrf); ?>
    <button class="logout-button" type="submit">Keluar</button>
</form>
