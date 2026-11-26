<?php
$endpoint = (!empty($teacherEditor) ? '/guru/kuis/' : '/admin/kuis/') . $item['id'];
$draft = $item['draft'];
$isPublished = (bool) array_filter($item['versions'], static fn (array $v): bool => $v['published_at'] !== null);
$pending = formValue($submitted, 'action');
?>
<a class="breadcrumb" href="<?= !empty($teacherEditor) ? '/guru/kuis' : '/admin/kuis' ?>">Daftar kuis</a>
<header class="page-header"><h1><?= h($item['title']) ?></h1><p class="muted"><?= h($item['slug']) ?></p></header>
<?php require __DIR__ . '/admin-feedback.php'; ?>
<div class="admin-grid">
    <div class="admin-stack">
        <?php if (!empty($teacherEditor)): ?>
        <section class="panel">
            <h2>Hasil siswa kelas saya</h2>
            <?php if ($isPublished): ?>
            <p>Unduh dokumen kuis dan hasil siswa yang saat ini berada dalam kelas Anda.</p>
            <div class="admin-shortcuts">
                <a class="button button-quiet" href="<?= h($endpoint) ?>/ekspor/nilai/pdf">Nilai PDF</a>
                <a class="button button-quiet" href="<?= h($endpoint) ?>/ekspor/nilai/xlsx">Nilai Excel</a>
                <a class="button button-quiet" href="<?= h($endpoint) ?>/ekspor/soal/pdf">Soal dan kunci PDF</a>
                <a class="button button-quiet" href="<?= h($endpoint) ?>/ekspor/soal/xlsx">Soal dan kunci Excel</a>
            </div>
            <?php endif; ?>
            <?php if (!$teacherResults): ?><p>Belum ada hasil.</p><?php endif; ?>
            <ul class="admin-list">
                <?php foreach ($teacherResults as $result): ?>
                <li><?= h($result['name']) ?> · <?= h($result['score']) ?>/100</li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
        <?php if (empty($teacherEditor)): ?><section class="panel">
            <h2>Sasaran kelas</h2>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="action" value="audience">
                <label class="field">Akses kuis
                    <select name="audience">
                        <option value="all_classes" <?= $item['audience'] === 'all_classes' ? 'selected' : '' ?>>Semua kelas</option>
                        <option value="selected_classes" <?= $item['audience'] === 'selected_classes' ? 'selected' : '' ?>>Kelas terpilih</option>
                    </select>
                </label>
                <?php foreach ($classes as $class): ?>
                <?php if ($class['archived_at'] !== null) continue; ?>
                <label class="check"><input type="checkbox" name="classes[]" value="<?= h($class['id']) ?>" <?= in_array((int) $class['id'], $quizClasses, true) ? 'checked' : '' ?>> <?= h($class['name']) ?> · <?= h($class['academic_year']) ?></label>
                <?php endforeach; ?>
                <button class="button button-quiet" type="submit">Simpan sasaran</button>
            </form>
        </section><?php endif; ?>
        <section class="panel" aria-labelledby="quiz-details">
            <h2 id="quiz-details">Informasi katalog</h2>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="action" value="update">
                <?php $values = $pending === 'update' ? $submitted : $item; require __DIR__ . '/admin-quiz-fields.php'; ?>
                <button class="button button-quiet" type="submit">Simpan informasi</button>
            </form>
        </section>
        <?php if ($draft): ?>
        <section class="panel" aria-labelledby="draft-settings">
            <h2 id="draft-settings">Pengaturan draf v<?= h($draft['version']) ?></h2>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="action" value="settings">
                <input type="hidden" name="draft_id" value="<?= h($draft['id']) ?>">
                <?php
                $values = $pending === 'settings' ? $submitted : [
                    'minutes' => (int) ceil($draft['duration_seconds'] / 60), 'passing' => $draft['passing_score'],
                    'tutor_enabled' => $draft['tutor_enabled'] ? '1' : '0',
                ];
                require __DIR__ . '/admin-quiz-time.php';
                ?>
                <button class="button button-quiet" type="submit">Simpan pengaturan</button>
            </form>
        </section>
        <?php endif; ?>
        <section class="panel" aria-labelledby="quiz-status">
            <h2 id="quiz-status">Versi dan status</h2>
            <ul class="admin-list">
            <?php foreach ($item['versions'] as $version): ?>
                <li>Versi <?= h($version['version']) ?> <span class="tag <?= $version['published_at'] === null ? 'tag-warning' : 'tag-success' ?>">
                    <?= $version['published_at'] === null ? 'Draf' : 'Terbit' ?>
                </span></li>
            <?php endforeach; ?>
            </ul>
            <?php if (!$draft || $item['archived_at'] === null): ?>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="action" value="<?= $draft ? 'publish' : 'draft' ?>">
                <?php if ($draft): ?><input type="hidden" name="draft_id" value="<?= h($draft['id']) ?>"><?php endif; ?>
                <button class="button button-primary" type="submit"><?= $draft ? 'Terbitkan versi draf' : 'Buat draf revisi' ?></button>
            </form>
            <?php endif; ?>
            <?php if (empty($teacherEditor)): ?>
            <?php if ($item['archived_at'] !== null): ?>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="action" value="restore">
                <button class="button button-quiet" type="submit">Pulihkan kuis</button>
            </form>
            <?php elseif ($isPublished): ?>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="action" value="archive">
                <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi arsip kuis</label>
                <button class="button button-danger" type="submit">Arsipkan kuis</button>
            </form>
            <?php endif; ?>
            <?php if (!$isPublished): ?>
            <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                <?php csrfField($csrf); ?>
                <input type="hidden" name="action" value="delete">
                <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi hapus draf beserta soal</label>
                <button class="button button-danger" type="submit">Hapus draf kuis</button>
            </form>
            <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
    <section class="panel" aria-labelledby="questions-title">
        <h2 id="questions-title"><?= $draft ? 'Soal draf' : 'Soal versi terbit' ?></h2>
        <?php if (!$item['questions']): ?><p class="muted">Belum ada soal.</p><?php endif; ?>
        <div class="admin-list">
            <?php foreach ($item['questions'] as $index => $question): ?>
                <?php $editing = (string) formValue($submitted, 'question_id') === (string) $question['id']; ?>
                <details class="admin-item" <?= $editing ? 'open' : '' ?>>
                    <summary><?= $index + 1 ?>. <?= h($question['stem']) ?></summary>
                    <?php if ($draft): ?>
                    <?php $values = $editing && $pending === 'question-save' ? $submitted : []; require __DIR__ . '/admin-question-form.php'; ?>
                    <form class="admin-form" method="post" action="<?= h($endpoint) ?>">
                        <?php csrfField($csrf); ?>
                        <input type="hidden" name="action" value="question-delete">
                        <input type="hidden" name="draft_id" value="<?= h($draft['id']) ?>">
                        <input type="hidden" name="question_id" value="<?= h($question['id']) ?>">
                        <label class="check"><input type="checkbox" name="confirm" value="1" required> Konfirmasi hapus soal</label>
                        <button class="button button-danger" type="submit">Hapus soal</button>
                    </form>
                    <?php else: ?>
                        <?php if ($question['snippet'] !== null): ?><pre class="code-block"><code><?= h($question['snippet']) ?></code></pre><?php endif; ?>
                        <ul class="admin-list">
                            <?php foreach ($question['options'] as $option): ?>
                                <li><?= h($option['label']) ?> <?= $option['correct'] ? '<span class="tag tag-success">Kunci</span>' : '' ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p><?= h($question['explanation']) ?></p>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
        </div>
        <?php if ($draft): ?>
        <details class="admin-item" <?= !$item['questions'] || ($pending === 'question-save' && formValue($submitted, 'question_id') === '') ? 'open' : '' ?>>
            <summary>Tambah soal</summary>
            <?php $question = null; $values = $pending === 'question-save' && formValue($submitted, 'question_id') === '' ? $submitted : []; require __DIR__ . '/admin-question-form.php'; ?>
        </details>
        <?php endif; ?>
    </section>
</div>
