<?php $isEdit = isset($item); $selected = $isEdit ? $item : $submitted; $chosen = is_array($selected['prerequisites'] ?? null) ? array_map('intval', $selected['prerequisites']) : []; ?>
<form class="admin-form" method="post" action="<?= h($base) ?>">
    <?php csrfField($csrf); ?>
    <input type="hidden" name="action" value="save">
    <?php if ($isEdit): ?><input type="hidden" name="node_id" value="<?= h($item['id']) ?>"><input type="hidden" name="class_id" value="<?= h($item['class_id'] ?? 0) ?>"><?php endif; ?>
    <label class="field">Nama kompetensi<input name="title" maxlength="120" required value="<?= h(formValue($selected, 'title')) ?>"></label>
    <label class="field">Deskripsi<textarea name="description" maxlength="2000" rows="3" required><?= h(formValue($selected, 'description')) ?></textarea></label>
    <label class="field">Topik<select name="topic" required><option value="">Pilih topik</option><?php foreach ($topics as $topic): ?><option value="<?= h($topic['slug']) ?>" <?= formValue($selected, 'topic') === $topic['slug'] ? 'selected' : '' ?>><?= h($topic['name']) ?></option><?php endforeach; ?></select></label>
    <?php if (!$isEdit): ?>
    <label class="field">Kelas<select name="class_id" required><?php if (!$teacher): ?><option value="0">Semua kelas</option><?php else: ?><option value="">Pilih kelas</option><?php endif; ?><?php foreach ($classes as $class): if (!empty($class['archived_at'])) continue; ?><option value="<?= h($class['id']) ?>"><?= h($class['name']) ?> · <?= h($class['academic_year']) ?></option><?php endforeach; ?></select></label>
    <?php endif; ?>
    <label class="field">Kuis kompetensi<select name="quiz_id"><option value="0">Belum ada kuis</option><?php foreach ($quizzes as $quiz): ?><option value="<?= h($quiz['id']) ?>" <?= (int) formValue($selected, 'quiz_id', 0) === (int) $quiz['id'] ? 'selected' : '' ?>><?= h($quiz['title']) ?></option><?php endforeach; ?></select></label>
    <?php if ($prerequisiteOptions): ?><fieldset><legend>Prasyarat</legend><?php foreach ($prerequisiteOptions as $option): if ($isEdit && $option['id'] === $item['id']) continue; ?><label><input type="checkbox" name="prerequisites[]" value="<?= h($option['id']) ?>" <?= in_array((int) $option['id'], $chosen, true) ? 'checked' : '' ?>> <?= h($option['title']) ?> · <?= h($option['class_name'] ?? 'Semua kelas') ?></label><?php endforeach; ?></fieldset><?php endif; ?>
    <button class="button button-primary" type="submit"><?= $isEdit ? 'Simpan versi baru' : 'Buat kompetensi' ?></button>
</form>
<?php unset($item, $selected, $isEdit, $chosen); ?>
