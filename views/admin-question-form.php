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
<form class="admin-form" data-question-editor method="post" action="<?= h($endpoint) ?>">
    <?php csrfField($csrf); ?>
    <input type="hidden" name="action" value="question-save">
    <input type="hidden" name="draft_id" value="<?= h($draft['id']) ?>">
    <?php if ($question): ?><input type="hidden" name="question_id" value="<?= h($question['id']) ?>"><?php endif; ?>
    <label class="field">Pertanyaan<textarea name="stem" maxlength="3000" rows="3" placeholder="Tulis pertanyaan di sini" required><?= h(formValue($values, 'stem', $question['stem'] ?? '')) ?></textarea></label>
    <details class="admin-code-toggle" <?= formValue($values, 'snippet', $question['snippet'] ?? '') !== '' ? 'open' : '' ?>>
        <summary>Tambahkan potongan kode (opsional)</summary>
        <label class="field">Potongan kode<textarea class="admin-code" name="snippet" maxlength="6000" rows="3"><?= h(formValue($values, 'snippet', $question['snippet'] ?? '')) ?></textarea></label>
    </details>
    <fieldset class="admin-options">
        <legend>Pilihan jawaban</legend>
        <p class="muted">Isi minimal dua pilihan, lalu pilih satu jawaban yang benar.</p>
        <?php foreach (['a', 'b', 'c', 'd', 'e'] as $index => $letter): ?>
        <div class="admin-option" data-option-row>
            <label class="field">Pilihan <?= strtoupper($letter) ?><?= $index >= 2 ? ' (opsional)' : '' ?>
                <input name="<?= $letter ?>" maxlength="400" placeholder="Tulis pilihan <?= strtoupper($letter) ?>" value="<?= h(formValue($values, $letter, $options[$index + 1]['label'] ?? '')) ?>" <?= $index < 2 ? 'required' : '' ?>>
            </label>
            <label class="check"><input name="correct" type="radio" value="<?= $letter ?>" aria-label="Jawaban benar <?= strtoupper($letter) ?>" <?= formValue($values, 'correct', $currentKey) === $letter ? 'checked' : '' ?> required> Benar</label>
        </div>
        <?php endforeach; ?>
        <button class="button button-quiet" type="button" data-add-option hidden>Tambah pilihan jawaban</button>
    </fieldset>
    <label class="field">Pembahasan<textarea name="explanation" maxlength="4000" rows="3" placeholder="Jelaskan alasan jawaban yang benar" required><?= h(formValue($values, 'explanation', $question['explanation'] ?? '')) ?></textarea></label>
    <button class="button button-primary" type="submit"><?= $question ? 'Simpan perubahan soal' : 'Tambah soal' ?></button>
</form>
