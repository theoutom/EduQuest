<label class="field">Judul<input name="title" maxlength="140" value="<?= h(formValue($values, 'title')) ?>" required></label>
<label class="field">Ringkasan<textarea name="summary" maxlength="600" rows="3" required><?= h(formValue($values, 'summary')) ?></textarea></label>
<div class="admin-field-row">
    <label class="field">Topik
        <select name="topic" required>
            <option value="">Pilih topik</option>
            <?php foreach ($topics as $topic): ?>
            <option value="<?= h($topic['slug']) ?>" <?= formValue($values, 'topic') === $topic['slug'] ? 'selected' : '' ?>><?= h($topic['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">Tingkat
        <select name="level" required>
            <?php foreach ($levels as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= formValue($values, 'level') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="field">Batas percobaan<input name="attempt_limit" type="number" min="1" max="50" value="<?= h(formValue($values, 'attempt_limit', 3)) ?>" required></label>
</div>
