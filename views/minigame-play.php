<a class="breadcrumb" href="/minigame">Semua permainan</a>
<header class="page-header"><h1><?= h($game['title']) ?></h1><p><?= h($game['instruction']) ?></p></header>
<section class="panel">
    <h2>Urutkan langkah</h2>
    <p>Pilih satu langkah berbeda untuk setiap urutan. Gunakan keyboard atau sentuh pilihan.</p>
    <?php if ($message !== ''): ?><p class="notice notice-error" role="alert"><?= h($message) ?></p><?php endif; ?>
    <form class="admin-form" method="post" action="/minigame/<?= h($game['id']) ?>">
        <?php csrfField($csrf); ?><input type="hidden" name="submission" value="<?= h($submission) ?>">
        <?php foreach ($game['steps'] as $index => $_): ?>
            <label class="field">Langkah <?= h($index + 1) ?>
                <select name="order[]" required><option value="">Pilih langkah</option>
                    <?php foreach ($game['steps'] as $step): ?>
                        <option value="<?= h($step['token']) ?>" <?= ($order[$index] ?? '') === $step['token'] ? 'selected' : '' ?>><?= h($step['body']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endforeach; ?>
        <button class="button button-primary" type="submit">Periksa urutan</button>
    </form>
</section>
