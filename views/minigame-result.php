<a class="breadcrumb" href="/minigame">Semua permainan</a>
<header class="page-header"><h1>Hasil: <?= h($result['title']) ?></h1></header>
<section class="panel">
    <h2><?= $result['correct'] ? 'Urutan benar' : 'Urutan belum tepat' ?></h2>
    <p>XP yang diperoleh pada percobaan ini: <?= h($result['earned_xp']) ?> XP</p>
    <h3>Urutan yang benar</h3>
    <ol><?php foreach ($result['correct_steps'] as $step): ?><li><?= h($step) ?></li><?php endforeach; ?></ol>
    <h3>Pembahasan</h3><p><?= nl2br(h($result['explanation'])) ?></p>
    <a class="button button-primary" href="/minigame/<?= h($result['game_id']) ?>">Coba lagi</a>
</section>
