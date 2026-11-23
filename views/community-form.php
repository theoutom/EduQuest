<a class="breadcrumb" href="/komunitas/saya">Pengajuan saya</a>
<header class="page-header">
    <h1><?= h($title) ?></h1>
    <p class="muted">Admin memeriksa soal sebelum siswa lain bisa mengerjakannya.</p>
</header>
<?php if ($message !== ''): ?><p class="notice notice-error admin-feedback" role="alert"><?= h($message) ?></p><?php endif; ?>
<section class="panel community-form-panel">
    <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
        <?php csrfField($csrf); ?>
        <input type="hidden" name="action" value="<?= h($action) ?>">
        <label class="field">Judul soal<input name="title" maxlength="140" required value="<?= h(formValue($values, 'title')) ?>"></label>
        <div class="admin-field-row">
            <label class="field">Topik<select name="topic" required>
                <option value="">Pilih topik</option>
                <?php foreach ($topics as $topic): ?><option value="<?= h($topic['slug']) ?>" <?= formValue($values, 'topic') === $topic['slug'] ? 'selected' : '' ?>><?= h($topic['name']) ?></option><?php endforeach; ?>
            </select></label>
            <label class="field">Tingkat<select name="level" required>
                <?php foreach (Community::LEVELS as $value => $label): ?><option value="<?= h($value) ?>" <?= formValue($values, 'level') === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
            </select></label>
        </div>
        <label class="field">Pertanyaan<textarea name="stem" rows="3" maxlength="3000" required><?= h(formValue($values, 'stem')) ?></textarea></label>
        <fieldset class="admin-options">
            <legend>Pilihan dan kunci</legend>
            <p class="muted">Isi dua pilihan atau lebih secara berurutan, lalu tandai kuncinya.</p>
            <?php foreach (['a', 'b', 'c', 'd', 'e'] as $index => $letter): ?>
                <div class="admin-option">
                    <label class="field">Pilihan <?= strtoupper($letter) ?><?= $index > 1 ? ' (opsional)' : '' ?>
                        <input name="<?= $letter ?>" maxlength="400" <?= $index < 2 ? 'required' : '' ?> value="<?= h(formValue($values, $letter)) ?>">
                    </label>
                    <label class="check"><input name="correct" value="<?= $letter ?>" type="radio" required <?= formValue($values, 'correct') === $letter ? 'checked' : '' ?>> Kunci <?= strtoupper($letter) ?></label>
                </div>
            <?php endforeach; ?>
        </fieldset>
        <label class="field">Pembahasan<textarea name="explanation" rows="3" maxlength="4000" required><?= h(formValue($values, 'explanation')) ?></textarea></label>
        <button class="button button-primary" type="submit"><?= $action === 'update' ? 'Simpan perubahan' : 'Kirim untuk ditinjau' ?></button>
    </form>
</section>
