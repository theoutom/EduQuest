<header class="page-header"><h1>Riwayat Admin</h1><p class="muted">Pantau percobaan siswa dan perubahan yang dilakukan pengelola.</p></header>
<div class="admin-stack">
    <section class="panel" aria-labelledby="attempt-history">
        <h2 id="attempt-history">Percobaan kuis terbaru</h2>
        <?php if (!$attempts): ?>
            <p class="muted">Belum ada percobaan.</p>
        <?php else: ?>
        <div class="table-scroll" tabindex="0" role="region" aria-labelledby="attempt-history">
            <table class="admin-table">
                <thead><tr><th scope="col">Siswa</th><th scope="col">Kuis</th><th scope="col">Versi</th><th scope="col">Status</th><th scope="col">Mulai</th></tr></thead>
                <tbody>
                <?php foreach ($attempts as $record): ?>
                    <tr><td><?= h($record['name']) ?></td><td><?= h($record['title']) ?></td><td><?= h($record['version']) ?></td>
                        <td><?= $record['score'] !== null ? h($record['score']) . '/100' : ($record['expired'] ? 'Waktu habis' : 'Sedang dikerjakan') ?></td>
                        <td><?= h(localDate($record['started_at'])) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
    <section class="panel" aria-labelledby="change-history">
        <h2 id="change-history">Perubahan admin terbaru</h2>
        <?php if (!$changes): ?>
            <p class="muted">Belum ada perubahan.</p>
        <?php else: ?>
        <?php $names = [
            'create_topic' => 'Tambah topik', 'update_topic' => 'Ubah topik', 'delete_topic' => 'Hapus topik',
            'create_quiz' => 'Buat kuis', 'update_quiz' => 'Ubah informasi kuis', 'settings_quiz' => 'Ubah pengaturan draf',
            'draft_quiz' => 'Buat draf revisi', 'publish_quiz' => 'Terbitkan kuis', 'archive_quiz' => 'Arsipkan kuis',
            'restore_quiz' => 'Pulihkan kuis', 'delete_quiz' => 'Hapus draf kuis', 'question-save_quiz' => 'Simpan soal',
            'question-delete_quiz' => 'Hapus soal', 'create_student' => 'Buat akun siswa', 'update_student' => 'Ubah nama siswa',
            'reset_student' => 'Atur ulang kata sandi siswa', 'disable_student' => 'Nonaktifkan siswa', 'delete_student' => 'Hapus akun siswa',
            'community_publish' => 'Terbitkan soal komunitas', 'community_reject' => 'Minta revisi soal komunitas',
            'community_archive' => 'Arsipkan soal komunitas', 'community_restore' => 'Pulihkan soal komunitas',
            'community_hide-comment' => 'Sembunyikan komentar komunitas',
            'mentor_create' => 'Tambah mentor', 'mentor_update' => 'Ubah profil mentor',
            'mentor_disable' => 'Nonaktifkan mentor', 'mentor_enable' => 'Aktifkan mentor',
            'mentor_add-slot' => 'Tambah jadwal mentor', 'mentor_close-slot' => 'Tutup jadwal mentor',
            'mentor_open-slot' => 'Buka jadwal mentor', 'mentor_booking_confirm' => 'Konfirmasi sesi mentor',
            'mentor_booking_reject' => 'Tolak sesi mentor', 'mentor_booking_cancel' => 'Batalkan sesi mentor',
            'daily_challenge_save' => 'Jadwalkan tantangan harian', 'daily_challenge_remove' => 'Cabut tantangan harian',
            'quest_create' => 'Terbitkan quest', 'quest_archive' => 'Hentikan quest',
            'minigame_create' => 'Buat permainan', 'minigame_archive' => 'Hentikan permainan',
            'mastery_save' => 'Simpan kompetensi',
            'create_exam' => 'Buat jadwal ujian', 'update_exam' => 'Ubah jadwal ujian', 'publish_exam' => 'Terbitkan jadwal ujian',
            'withdraw_exam' => 'Tarik jadwal ujian', 'delete_exam' => 'Hapus jadwal ujian',
        ]; ?>
        <div class="table-scroll" tabindex="0" role="region" aria-labelledby="change-history">
            <table class="admin-table">
                <thead><tr><th scope="col">Pengelola</th><th scope="col">Tindakan</th><th scope="col">Data terkait</th><th scope="col">Waktu</th></tr></thead>
                <tbody>
                <?php foreach ($changes as $record): ?>
                    <tr><td><?= h($record['name']) ?></td><td><?= h($names[$record['action']] ?? $record['action']) ?></td>
                        <td><?= h($record['target']) ?></td><td><?= h(localDate($record['happened_at'])) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>
