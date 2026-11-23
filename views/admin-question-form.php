<?php
$options = array_column($question['options'] ?? [], null, 'position');
$currentKey = '';
foreach ($options as $position => $option) {
    if ($option['correct']) {
        $currentKey = chr(96 + $position);
        break;
    }
}
?>
<form class="admin-form" method="post" action="<?= h($endpoint) ?>">
    <?php csrfField($csrf); ?>
    <input type="hidden" name="action" value="question-save">
    <input type="hidden" name="draft_id" value="<?= h($draft['id']) ?>">
    <?php if ($question): ?><input type="hidden" name="question_id" value="<?= h($question['id']) ?>"><?php endif; ?>
    <label class="field">Pertanyaan<textarea name="stem" maxlength="3000" rows="3" required><?= h(formValue($values, 'stem', $question['stem'] ?? '')) ?></textarea></label>
    <label class="field">Potongan kode <span class="muted">(opsional)</span><textarea class="admin-code" name="snippet" maxlength="6000" rows="3"><?= h(formValue($values, 'snippet', $question['snippet'] ?? '')) ?></textarea></label>
    <fieldset class="admin-options">
        <legend>Pilihan jawaban</legend>
        <?php foreach (['a', 'b', 'c', 'd', 'e'] as $index => $letter): ?>
        <div class="admin-option">
            <label class="field">Pilihan <?= strtoupper($letter) ?><?= $index >= 2 ? ' (opsional)' : '' ?>
                <input name="<?= $letter ?>" maxlength="400" value="<?= h(formValue($values, $letter, $options[$index + 1]['label'] ?? '')) ?>" <?= $index < 2 ? 'required' : '' ?>>
            </label>
            <label class="check"><input name="correct" type="radio" value="<?= $letter ?>" <?= formValue($values, 'correct', $currentKey) === $letter ? 'checked' : '' ?> required> Kunci <?= strtoupper($letter) ?></label>
        </div>
        <?php endforeach; ?>
    </fieldset>
    <label class="field">Pembahasan<textarea name="explanation" maxlength="4000" rows="3" required><?= h(formValue($values, 'explanation', $question['explanation'] ?? '')) ?></textarea></label>
    <button class="button button-primary" type="submit"><?= $question ? 'Simpan perubahan soal' : 'Tambah soal' ?></button>
</form>
