<?php if ($message !== ''): ?>
    <p class="notice notice-error admin-feedback" role="alert"><?= h($message) ?></p>
<?php elseif ($saved): ?>
    <p class="notice admin-feedback" role="status">Perubahan tersimpan.</p>
<?php endif; ?>
