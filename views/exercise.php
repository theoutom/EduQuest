<?php
$questions = $attempt['questions'];
$total = count($questions);
$answered = $total - $blank;
$seconds = (int) ceil(max(0, $attempt['deadline_ms'] - $attempt['now_ms']) / 1000);
$clockText = sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
$tutorEndpoint = $base . '/tutor';
$tutorPosition = $position;
?>
<a class="breadcrumb" href="/kuis/<?= h($attempt['slug']) ?>">Pengantar kuis</a>
<header class="page-header exercise-header">
    <div>
        <h1><?= h($attempt['title']) ?></h1>
        <p class="muted">Soal <?= $position ?> dari <?= $total ?></p>
    </div>
    <div class="clock" data-clock-box>
        <span>Sisa waktu</span>
        <strong data-clock data-deadline="<?= h($attempt['deadline_ms']) ?>" data-now="<?= h($attempt['now_ms']) ?>"><?= h($clockText) ?></strong>
    </div>
</header>
<div class="exercise-grid">
    <section class="panel exercise-panel" aria-labelledby="question-title">
        <form method="post" action="<?= h($base) ?>/jawaban" id="answer-form" data-answer-form data-blank="<?= $blank ?>" data-start-selected="<?= $current['selected_id'] === null ? '0' : '1' ?>">
            <?php csrfField($csrf); ?>
            <input type="hidden" name="question" value="<?= h($current['id']) ?>">
            <input type="hidden" name="position" value="<?= $position ?>">
            <div class="question-top">
                <p class="tag"><?= h($attempt['topic_name']) ?></p>
                <label class="check"><input type="checkbox" name="doubtful" value="1" <?= $current['doubtful'] ? 'checked' : '' ?>> Ragu-ragu</label>
            </div>
            <h2 id="question-title"><?= h($current['stem']) ?></h2>
            <?php if ($current['snippet'] !== null): ?><pre class="code-block" tabindex="0"><code><?= h($current['snippet']) ?></code></pre><?php endif; ?>
            <fieldset class="choices">
                <legend>Pilihan jawaban</legend>
                <?php foreach ($current['options'] as $index => $option): ?>
                    <label class="choice">
                        <input type="radio" name="option" value="<?= h($option['id']) ?>" <?= (int) $current['selected_id'] === $option['id'] ? 'checked' : '' ?>>
                        <span class="option-letter" aria-hidden="true"><?= chr(65 + $index) ?></span>
                        <span><?= h($option['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <div class="question-tools">
                <button class="button button-quiet" type="button" data-clear hidden>Hapus pilihan</button>
                <?php if (!publicDemo() && $attempt['tutor_enabled']): ?>
                    <button class="button button-quiet" data-tutor-open type="submit" name="target" value="tutor">Tutor latihan</button>
                <?php endif; ?>
                <p class="save-status" data-save-status role="status"><?= $current['selected_id'] !== null || $current['doubtful'] ? 'Tersimpan' : '' ?></p>
                <button class="button button-quiet" type="button" data-retry hidden>Simpan ulang</button>
            </div>
            <div class="question-actions">
                <?php if ($position > 1): ?>
                    <button class="button button-quiet" type="submit" name="target" value="<?= $position - 1 ?>">Soal sebelumnya</button>
                <?php endif; ?>
                <button class="button button-quiet" type="submit" name="target" value="<?= $position ?>">Simpan jawaban</button>
                <?php if ($position < $total): ?>
                    <button class="button button-primary" type="submit" name="target" value="<?= $position + 1 ?>">Simpan &amp; lanjut</button>
                <?php else: ?>
                    <button class="button button-primary" type="submit" name="target" value="kumpulkan">Kumpulkan kuis</button>
                <?php endif; ?>
            </div>
        </form>
    </section>
    <aside class="panel question-map" aria-labelledby="map-title">
        <h2 id="map-title">Peta soal</h2>
        <p class="muted"><span data-answered><?= $answered ?></span> dari <?= $total ?> terjawab</p>
        <progress max="<?= $total ?>" value="<?= $answered ?>" aria-label="Jumlah soal terjawab" data-progress><?= $answered ?>/<?= $total ?></progress>
        <div class="question-grid">
            <?php foreach ($questions as $index => $question): ?>
                <?php
                $number = $index + 1;
                $state = $question['doubtful'] ? 'ragu' : ($question['selected_id'] !== null ? 'terjawab' : 'kosong');
                ?>
                <button type="submit" name="target" value="<?= $number ?>" form="answer-form"
                    class="question-number" data-state="<?= $state ?>" <?= $number === $position ? 'aria-current="step" data-current-question' : '' ?>
                    aria-label="Soal <?= $number ?>, <?= $state === 'ragu' ? 'ragu-ragu' : $state ?>"><?= $number ?></button>
            <?php endforeach; ?>
        </div>
        <ul class="map-legend">
            <li><span data-state="terjawab"></span>Terjawab</li>
            <li><span data-state="ragu"></span>Ragu-ragu</li>
            <li><span data-state="kosong"></span>Kosong</li>
        </ul>
        <?php if ($position < $total): ?>
            <button type="submit" form="answer-form" name="target" value="kumpulkan" class="button button-quiet w-full">Kumpulkan kuis</button>
        <?php endif; ?>
    </aside>
</div>

<?php if (!publicDemo() && $attempt['tutor_enabled']): require __DIR__ . '/tutor-panel.php'; endif; ?>
<dialog class="dialog" data-confirm aria-labelledby="dialog-title">
    <div class="dialog-content">
        <h2 id="dialog-title">Kumpulkan kuis?</h2>
        <p class="muted" data-confirm-copy><?= $blank ?> soal belum dijawab.</p>
        <div class="dialog-actions">
            <form method="dialog"><button type="submit" class="button button-quiet" autofocus>Kembali ke soal</button></form>
            <form method="post" action="<?= h($base) ?>/kumpulkan" data-submit-form>
                <?php csrfField($csrf); ?>
                <button class="button button-primary" type="submit">Ya, kumpulkan</button>
            </form>
        </div>
    </div>
</dialog>
