<p class="muted">Pilih jenis bantuan. Tutor membantu menelusuri langkah dan konsep.</p>
<form class="admin-form" method="post" action="<?= h($tutorEndpoint) ?>" data-tutor-form>
    <?php csrfField($csrf); ?>
    <?php if ($tutorPosition !== null): ?><input type="hidden" name="position" value="<?= $tutorPosition ?>"><?php endif; ?>
    <div class="tutor-actions">
        <?php foreach (Tutor::INTENTS as $intent => $label): ?>
            <button class="button button-quiet" type="submit" name="intent" value="<?= h($intent) ?>"><?= h($label) ?></button>
        <?php endforeach; ?>
    </div>
</form>
<p class="tutor-status muted" role="status" data-tutor-status></p>
<div class="notice tutor-result" data-tutor-result <?= !isset($hint) ? 'hidden' : '' ?>>
    <h3>Petunjuk</h3>
    <p data-tutor-hint><?= h($hint['hint'] ?? '') ?></p>
    <p data-tutor-prompt><?= h($hint['prompt'] ?? '') ?></p>
    <small class="muted" data-tutor-source><?= match ($hint['source'] ?? null) {
        'ollama' => 'Tindak lanjut dipilih model lokal',
        '9router' => 'Tindak lanjut dipilih melalui 9Router',
        'vercel' => 'Tindak lanjut dipilih melalui AI Gateway',
        'panduan' => 'Panduan bawaan',
        default => '',
    } ?></small>
</div>
