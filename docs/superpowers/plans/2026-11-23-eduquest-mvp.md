# Cakupan MVP EduQuest

| Layar | Fungsi yang bisa dipakai | Kriteria yang masih terbuka |
| --- | --- | --- |
| Dashboard | Lanjut kuis, katalog, progres pribadi, aktivitas 7 hari, streak WIB, tantangan harian, jadwal ujian yang diterbitkan admin, sesi mentor terdekat | Peringkat dari kelas yang terdaftar dan pemberitahuan acara yang relevan |
| Daftar kuis | Cari dan filter topik, tingkat, dan status, mulai, lanjut, lihat hasil, simpan atau hapus simpanan pribadi | Rating perlu sumber ulasan yang sah |
| Profil | Identitas sesi, riwayat, progres, dan isian akademik pribadi yang bisa diedit siswa. Admin mengelola akses | Ganti kata sandi mandiri, preferensi notifikasi, verifikasi identitas, prestasi, dan kontak wali kelas |
| Pengantar kuis | Metadata, nilai lulus, percobaan tersisa, riwayat dan aturan | Peningkatan visual target dari nilai lulus yang sudah tersimpan. Proctoring memerlukan keputusan privasi tersendiri |
| Pengerjaan | Timer, penyimpanan, ragu-ragu, navigasi, kode, pilihan, pengumpulan | Visual pendukung soal perlu bisa ditambahkan admin dan muncul hanya bila materinya tersedia |
| Tutor | Pilihan bantuan terkurasi, mode latihan, fallback lokal | Verifikasi koneksi model nyata setelah setup 9Router dilanjutkan. Chat bebas perlu aturan privasi dan pencegahan kebocoran jawaban |
| Pembahasan | Nilai, status jawaban, perbandingan jawaban, penjelasan, filter, akurasi topik pada percobaan, dan cetak lengkap melalui browser | Penguasaan lintas percobaan memerlukan definisi agregasi |
| Komunitas | Pencarian, kontribusi, moderasi, latihan, suara, diskusi, tren dan kontributor | Pemisahan peran guru dari admin bergantung pada kebutuhan akun pengajar |
| Mentor | Direktori, tarif, verifikasi, slot, pesanan, pembatalan dan pertemuan eksternal privat | Ulasan dari pemesan yang menyelesaikan sesi, chat privat, FAQ dan pilihan spesialisasi perlu diuji terpisah. Pemesanan lokal membutuhkan data mentor asli |

## Kriteria penerimaan tahap ini

- [x] Siswa bisa menyimpan atau menghapus simpanan kuis. Permintaan ulang menghasilkan satu keadaan dan perubahan satu akun tidak muncul di akun lain. Formulir memakai CSRF dan tetap di filter asal.
- [x] Katalog memakai status yang berasal dari percobaan dan simpanan. Filter status bisa dipakai bersama pencarian, topik, dan tingkat.
- [x] Tampilan cetak menampilkan seluruh soal dan pembahasan milik siswa setelah selesai. Akses siswa lain ditolak. Tombol memanggil dialog browser untuk printer atau simpan PDF.
- [x] Jalankan migrasi dan refresh grant web, uji HTTP, browser, serta instalasi lokal.

`npm run build` dan `npm test` lulus dengan role PostgreSQL terbatas. Uji Chrome menjalankan kedua aksi simpanan, memeriksa pemanggilan dialog cetak, dan menghasilkan PDF di memori. Migrasi `008_quiz_bookmarks.sql` diterapkan dan katalog serta tampilan cetak melalui akun siswa terverifikasi di localhost.
