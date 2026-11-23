<label class="field">Nama mentor<input name="name" maxlength="100" required value="<?= h(formValue($values, 'name')) ?>"></label>
<label class="field">Bidang<select name="category" required>
    <option value="">Pilih bidang</option>
    <?php foreach (Mentor::CATEGORIES as $key => $category): ?><option value="<?= h($key) ?>" <?= formValue($values, 'category') === $key ? 'selected' : '' ?>><?= h($category) ?></option><?php endforeach; ?>
</select></label>
<label class="field">Profil dan spesialisasi<textarea name="bio" rows="4" maxlength="1600" required><?= h(formValue($values, 'bio')) ?></textarea></label>
<label class="field">Tarif per jam (Rp)<input type="number" name="rate" min="0" max="10000000" required value="<?= h(formValue($values, 'rate', 0)) ?>"></label>
<label class="check"><input type="checkbox" name="verified" value="1" <?= !empty($values['verified']) ? 'checked' : '' ?>> Profil telah diverifikasi</label>
