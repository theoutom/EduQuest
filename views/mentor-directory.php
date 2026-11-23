<header class="page-header"><h1>Cari Mentor</h1><p class="muted">Cari bidang yang ingin kamu pelajari dan pilih waktu untuk sesi satu lawan satu.</p></header>
<nav class="admin-shortcuts" aria-label="Pemesanan mentor"><a class="button button-quiet" href="/mentor/sesi">Sesi saya</a></nav>
<form class="panel filters community-filters" method="get" action="/mentor">
    <label class="field">Cari mentor<input type="search" name="q" maxlength="120" value="<?= h($filters['q']) ?>"></label>
    <label class="field">Bidang<select name="kategori"><option value="">Semua bidang</option>
        <?php foreach (Mentor::CATEGORIES as $key => $category): ?><option value="<?= h($key) ?>" <?= $filters['kategori'] === $key ? 'selected' : '' ?>><?= h($category) ?></option><?php endforeach; ?>
    </select></label>
    <label class="field">Maksimum tarif per jam (Rp)<input type="number" name="tarif" min="0" max="10000000" value="<?= h($filters['tarif']) ?>"></label>
    <label class="check"><input type="checkbox" name="tersedia" value="1" <?= $filters['tersedia'] ? 'checked' : '' ?>> Ada jadwal</label>
    <button class="button button-primary" type="submit">Cari mentor</button>
</form>
<div class="detail-grid community-content">
    <section class="admin-stack" aria-label="Daftar mentor">
        <?php if (!$items): ?><section class="panel"><h2>Belum ada mentor yang cocok</h2><p class="muted">Coba ubah pencarian atau periksa lagi ketika jadwal baru tersedia.</p></section><?php endif; ?>
        <?php foreach ($items as $mentor): ?>
        <article class="panel community-card">
            <div class="tag-row"><span class="tag"><?= h(Mentor::CATEGORIES[$mentor['category']]) ?></span>
                <?php if ($mentor['verified']): ?><span class="tag tag-success">Terverifikasi</span><?php endif; ?>
            </div>
            <h2><a href="/mentor/<?= h($mentor['id']) ?>"><?= h($mentor['name']) ?></a></h2>
            <p class="community-text"><?= h(mb_strimwidth($mentor['bio'], 0, 220, '…')) ?></p>
            <footer class="section-heading"><strong><?= h(mentorRate($mentor['rate'])) ?></strong>
                <span class="muted"><?= $mentor['next_slot'] ? 'Slot berikutnya ' . h(localDate($mentor['next_slot'])) : 'Belum ada slot terbuka' ?></span>
                <a class="button button-quiet" href="/mentor/<?= h($mentor['id']) ?>">Lihat jadwal<span class="sr-only"> <?= h($mentor['name']) ?></span></a>
            </footer>
        </article>
        <?php endforeach; ?>
    </section>
    <aside class="panel">
        <h2>Pemesanan sesi</h2>
        <p class="muted">Pilih slot dan tulis topik yang ingin dibahas. Setelah admin mengonfirmasi, tautan pertemuan muncul di Sesi saya.</p>
    </aside>
</div>
