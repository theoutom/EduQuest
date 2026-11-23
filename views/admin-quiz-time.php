<div class="admin-field-row">
    <label class="field">Durasi (menit)<input name="minutes" type="number" min="1" max="180" value="<?= h(formValue($values, 'minutes', 10)) ?>" required></label>
    <label class="field">Nilai lulus<input name="passing" type="number" min="0" max="100" value="<?= h(formValue($values, 'passing', 70)) ?>" required></label>
</div>
<label class="check"><input name="tutor_enabled" type="checkbox" value="1" <?= formValue($values, 'tutor_enabled') === '1' ? 'checked' : '' ?>> Mode latihan dengan Tutor</label>
