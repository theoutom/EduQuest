<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> | EduQuest</title>
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#konten">Lompat ke konten</a>
<?php if ($student): ?>
    <div class="shell">
        <aside class="sidebar" aria-label="Sidebar">
            <?php require __DIR__ . '/navigation.php'; ?>
        </aside>
        <div class="shell-main">
            <header class="topbar">
                <div class="topbar-inner">
                    <details class="mobile-nav">
                        <summary class="button button-quiet"><span class="menu-mark" aria-hidden="true"></span> Menu</summary>
                        <div class="mobile-nav-panel"><?php require __DIR__ . '/navigation.php'; ?></div>
                    </details>
                    <span class="topbar-label"><?= $isAdmin ? 'Ruang administrasi' : ($isTeacher ? 'Ruang guru' : 'Ruang belajar') ?></span>
                    <a class="account-link" href="<?= $isAdmin ? '/admin' : ($isTeacher ? '/guru' : '/profil') ?>">
                        <span class="avatar" aria-hidden="true"><?= h(mb_substr($student['name'], 0, 1)) ?></span>
                        <span><?= h($student['name']) ?></span>
                    </a>
                </div>
            </header>
            <main class="workspace" id="konten" tabindex="-1">
                <?php require __DIR__ . '/' . $view . '.php'; ?>
            </main>
        </div>
    </div>
<?php else: ?>
    <main class="guest" id="konten" tabindex="-1">
        <?php require __DIR__ . '/' . $view . '.php'; ?>
    </main>
<?php endif; ?>
</body>
</html>
