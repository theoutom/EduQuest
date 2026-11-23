<header class="page-header">
    <h1>Profil dan Riwayat</h1>
    <p class="muted"><?= h($student['name']) ?></p>
</header>
<?php if ($saved): ?><p class="notice admin-feedback" role="status">Profil tersimpan.</p><?php endif; ?>
<div class="admin-grid">
<section class="panel" aria-labelledby="academic-profile">
    <h2 id="academic-profile">Profil akademik</h2>
    <p class="muted">Hanya tampil di akunmu. Semua isian opsional.</p>
    <form class="admin-form" method="post" action="/profil">
        <?php csrfField($csrf); ?>
        <?php foreach (StudentProfile::FIELDS as $key => [$label, $limit]): ?>
            <label class="field"><?= h($label) ?>
                <?php if ($key === 'bio'): ?>
                    <textarea name="<?= h($key) ?>" rows="4" maxlength="<?= h($limit) ?>"><?= h($details[$key]) ?></textarea>
                <?php else: ?>
                    <input name="<?= h($key) ?>" maxlength="<?= h($limit) ?>" value="<?= h($details[$key]) ?>">
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
        <button class="button button-primary" type="submit">Simpan profil</button>
    </form>
</section>
<div class="admin-stack">
<section class="panel dashboard-stats" aria-labelledby="profile-progress">
    <h2 id="profile-progress">Progres belajar</h2>
    <dl>
        <div><dt>Kuis selesai</dt><dd><?= h($progress['completed']) ?></dd></div>
        <div><dt>Rata-rata nilai</dt><dd><?= $progress['average'] === null ? '—' : h($progress['average']) . '/100' ?></dd></div>
    </dl>
</section>
<section class="panel">
    <h2>Riwayat kuis</h2>
    <?php if ($history === []): ?><p class="muted">Belum ada percobaan kuis.</p><?php endif; ?>
    <ul class="history admin-history">
        <?php foreach ($history as $entry): ?>
        <li>
            <span><?= h($entry['title']) ?><?= $entry['score'] === null ? '' : ' · ' . h($entry['score']) . '/100' ?></span>
            <?php if ($entry['active']): ?>
                <a href="/sesi/<?= h($entry['id']) ?>">Lanjutkan sesi</a>
            <?php else: ?>
                <a href="/hasil/<?= h($entry['id']) ?>">Lihat pembahasan</a>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
</div>
</div>
