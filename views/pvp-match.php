<a class="breadcrumb" href="/arena">Arena Kuis</a>
<header class="page-header"><h1><?= h($match['title']) ?></h1><p><?= h($match['challenger_name']) ?> dan <?= h($match['opponent_name']) ?></p></header>
<?php if ($message !== ''): ?><p class="panel" role="alert"><?= h($message) ?></p><?php endif; ?>
<?php if ($match['state'] === 'cancelled'): ?>
    <section class="panel"><h2>Undangan berakhir</h2><p>Undangan dibatalkan atau sudah kedaluwarsa.</p></section>
<?php elseif ($match['state'] === 'pending'): ?>
    <section class="panel"><h2>Menunggu teman</h2><p>Undangan berakhir <?= h(localDate($match['invitation_expires_at'])) ?>.</p>
    <?php if ($playerId === (int) $match['opponent_id']): ?>
        <form method="post" action="/arena/<?= h($match['id']) ?>"><?php csrfField($csrf); ?><input type="hidden" name="action" value="accept"><button class="button button-primary" type="submit">Terima dan mulai</button></form>
    <?php endif; ?>
    <form method="post" action="/arena/<?= h($match['id']) ?>"><?php csrfField($csrf); ?><input type="hidden" name="action" value="cancel"><button class="button button-quiet" type="submit">Batalkan undangan</button></form>
    </section><script src="/assets/pvp-wait.js" defer></script>
<?php elseif ($match['state'] === 'running'): ?>
    <?php $mineFinished = $playerId === (int) $match['challenger_id'] ? $match['challenger_finished_at'] : $match['opponent_finished_at']; ?>
    <?php if (!$match['round_ready']): ?>
        <section class="panel"><h2>Persiapan ronde</h2><p>Kedua pemain akan mendapat soal pada waktu mulai yang sama.</p></section><script src="/assets/pvp-wait.js" defer></script>
    <?php else: ?>
    <section class="panel"><h2>Ronde berlangsung</h2><p>Batas waktu: <?= h(localDate($match['deadline'])) ?>. Setiap jawaban dikirim satu kali dan tidak dapat diubah.</p></section>
    <?php if ($mineFinished !== null): ?>
        <section class="panel"><p>Jawabanmu sudah dikumpulkan. Menunggu teman atau batas waktu berakhir.</p></section><script src="/assets/pvp-wait.js" defer></script>
    <?php else: ?>
        <?php foreach ($match['questions'] as $question): ?><section class="panel review-section"><h2>Soal <?= h($question['position']) ?></h2><p><?= h($question['stem']) ?></p>
        <?php if ($question['snippet'] !== null): ?><pre class="code-block" tabindex="0"><code><?= h($question['snippet']) ?></code></pre><?php endif; ?>
        <?php if ($question['selected_id'] !== null): ?><p>Jawaban terkirim.</p><?php else: ?>
        <form method="post" action="/arena/<?= h($match['id']) ?>" class="admin-form"><?php csrfField($csrf); ?><input type="hidden" name="action" value="answer"><input type="hidden" name="question" value="<?= h($question['id']) ?>">
            <fieldset><legend>Pilih jawaban</legend><?php foreach ($question['options'] as $option): ?><label><input type="radio" name="option" value="<?= h($option['id']) ?>" required> <?= h($option['label']) ?></label><br><?php endforeach; ?></fieldset>
            <button class="button button-quiet" type="submit">Kirim jawaban</button>
        </form><?php endif; ?></section><?php endforeach; ?>
        <form method="post" action="/arena/<?= h($match['id']) ?>"><?php csrfField($csrf); ?><input type="hidden" name="action" value="finish"><button class="button button-primary" type="submit">Kumpulkan pertandingan</button></form>
    <?php endif; ?>
    <?php endif; ?>
<?php elseif ($match['state'] === 'finished'): ?>
    <section class="panel"><h2>Hasil pertandingan</h2><p>Urutan berdasarkan jawaban benar, lalu waktu pengumpulan server. Waktu sama menghasilkan seri.</p>
        <?php $first = $match['results'][0]; $second = $match['results'][1]; ?>
        <?php if ((int) $first['correct'] === (int) $second['correct'] && $first['finished_at'] === $second['finished_at']): ?><p><strong>Hasil seri</strong></p><?php else: ?><p><strong><?= h($first['name']) ?> menang</strong></p><?php endif; ?>
        <ol><?php foreach ($match['results'] as $result): ?><li><?= h($result['name']) ?> · <?= h($result['correct']) ?> benar · <?= h(localDate($result['finished_at'])) ?></li><?php endforeach; ?></ol>
    </section>
    <section class="panel"><h2>Pembahasan</h2><?php foreach ($match['review'] as $question): ?><article><h3>Soal <?= h($question['position']) ?></h3><p><?= h($question['stem']) ?></p><p>Jawabanmu: <?= h($question['selected'] ?? 'Tidak dijawab') ?>. Jawaban benar: <?= h($question['correct']) ?>.</p><p><?= h($question['explanation']) ?></p></article><?php endforeach; ?></section>
<?php endif; ?>
