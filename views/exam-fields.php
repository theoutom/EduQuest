<?php
$start = isset($item) ? new DateTimeImmutable($item['starts_at']) : null;
$end = isset($item) ? new DateTimeImmutable($item['ends_at']) : null;
$wib = new DateTimeZone('Asia/Jakarta');
?>
<label class="field">Judul ujian<input name="title" maxlength="120" required value="<?= h(formValue($submitted, 'title', $item['title'] ?? '')) ?>"></label>
<div class="admin-field-row">
    <label class="field">Mulai (WIB)<input type="datetime-local" name="starts" required value="<?= h(formValue($submitted, 'starts', $start?->setTimezone($wib)->format('Y-m-d\TH:i') ?? '')) ?>"></label>
    <label class="field">Selesai (WIB)<input type="datetime-local" name="ends" required value="<?= h(formValue($submitted, 'ends', $end?->setTimezone($wib)->format('Y-m-d\TH:i') ?? '')) ?>"></label>
</div>
<label class="field">Lokasi<input name="location" maxlength="120" value="<?= h(formValue($submitted, 'location', $item['location'] ?? '')) ?>"></label>
<label class="field">Instruksi<textarea name="note" rows="3" maxlength="600"><?= h(formValue($submitted, 'note', $item['note'] ?? '')) ?></textarea></label>
