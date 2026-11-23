<header class="page-header">
    <h1>Bank Soal Komunitas</h1>
    <p class="muted">Kerjakan soal dari siswa lain atau bagikan latihanmu sendiri.</p>
</header>
<nav class="admin-shortcuts" aria-label="Kontribusi soal">
    <a class="button button-primary" href="/komunitas/buat">Bagikan soal</a>
    <a class="button button-quiet" href="/komunitas/saya">Pengajuan saya</a>
</nav>
<form class="panel filters community-filters" method="get" action="/komunitas">
    <label class="field">Cari soal<input name="q" type="search" maxlength="120" value="<?= h($filter['q']) ?>"></label>
    <label class="field">Topik<select name="topik">
        <option value="">Semua topik</option>
        <?php foreach ($topics as $option): ?><option value="<?= h($option['slug']) ?>" <?= $filter['topik'] === $option['slug'] ? 'selected' : '' ?>><?= h($option['name']) ?></option><?php endforeach; ?>
    </select></label>
    <label class="field">Tingkat<select name="level">
        <option value="">Semua tingkat</option>
        <?php foreach (Community::LEVELS as $value => $label): ?><option value="<?= h($value) ?>" <?= $filter['level'] === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
    </select></label>
    <label class="field">Urutkan<select name="urut">
        <?php foreach (Community::ORDERS as $value => $label): ?><option value="<?= h($value) ?>" <?= $filter['urut'] === $value ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
    </select></label>
    <button class="button button-primary" type="submit">Tampilkan soal</button>
</form>
<div class="detail-grid community-content">
    <section class="admin-stack" aria-label="Daftar soal komunitas">
        <?php if (!$items): ?>
        <div class="panel empty-state"><h2>Belum ada soal yang cocok</h2><p class="muted">Coba ubah filter atau bagikan soal untuk ditinjau.</p></div>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
        <article class="panel community-card">
            <div class="tag-row"><span class="tag"><?= h($item['topic_name']) ?></span><span class="tag tag-neutral"><?= h(Community::LEVELS[$item['level']]) ?></span><span class="tag tag-success">Disetujui admin</span></div>
            <h2><a href="/komunitas/<?= h($item['id']) ?>"><?= h($item['title']) ?></a></h2>
            <p class="muted"><?= h(mb_strimwidth($item['stem'], 0, 220, '…')) ?></p>
            <footer class="section-heading">
                <span>Oleh <?= h($item['author']) ?></span>
                <span class="muted"><?= h($item['votes']) ?> suara <span class="meta-separator"></span> <?= h($item['comments']) ?> komentar</span>
            </footer>
        </article>
        <?php endforeach; ?>
        <?php if ($number > 1 || $hasNext): ?>
        <nav class="admin-shortcuts" aria-label="Halaman soal">
            <?php if ($number > 1): ?><a class="button button-quiet" href="/komunitas?<?= h(http_build_query($filter + ['hal' => $number - 1])) ?>">Sebelumnya</a><?php endif; ?>
            <?php if ($hasNext && $number < 10000): ?><a class="button button-quiet" href="/komunitas?<?= h(http_build_query($filter + ['hal' => $number + 1])) ?>">Berikutnya</a><?php endif; ?>
        </nav>
        <?php endif; ?>
    </section>
    <aside class="admin-stack">
        <section class="panel"><h2>Topik populer</h2>
            <?php if (!$trending): ?><p class="muted">Topik muncul setelah soal diterbitkan.</p><?php endif; ?>
            <ul class="history admin-history">
                <?php foreach ($trending as $topic): ?><li><a href="/komunitas?<?= h(http_build_query(['topik' => $topic['slug']])) ?>"><?= h($topic['name']) ?></a><span><?= h($topic['questions']) ?> soal</span></li><?php endforeach; ?>
            </ul>
        </section>
        <section class="panel"><h2>Kontributor teratas</h2>
            <?php if (!$contributors): ?><p class="muted">Belum ada kontribusi terbit.</p><?php endif; ?>
            <ul class="history admin-history">
                <?php foreach ($contributors as $person): ?><li><span><?= h($person['name']) ?></span><span><?= h($person['questions']) ?> soal</span></li><?php endforeach; ?>
            </ul>
        </section>
    </aside>
</div>
