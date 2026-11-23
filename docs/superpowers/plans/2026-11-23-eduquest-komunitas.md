# Bank Soal Komunitas

- [x] Uji alur kontribusi, batas akses draf, moderasi, latihan, komentar dan voting dengan HTTP.
- [x] Tambahkan skema kontribusi pilihan ganda, jawaban latihan, suara unik dan komentar. Pisahkan izin moderasi dari koneksi siswa.
- [x] Buat katalog dengan pencarian, topik, tingkat, urutan, ringkasan topik populer dan kontributor. Buat halaman kontribusi pribadi, detail latihan, dan antrean moderasi admin.
- [x] Jalankan regresi dengan role PostgreSQL terbatas, terapkan migrasi lokal dan verifikasi rute siswa serta admin.

Soal hanya masuk katalog setelah persetujuan admin. Kontributor dapat memperbarui soal yang masih menunggu, menghapus pengajuan yang belum terbit, atau mengirim revisi sebagai pengajuan baru setelah penolakan. Konten yang telah terbit terkunci agar pembahasan latihan konsisten. Nama akun pengelola dan kata sandi tidak dikirim oleh modul komunitas.

Tes HTTP membuktikan bahwa sesi siswa tidak dapat memoderasi, kunci disembunyikan sebelum menjawab, suara ganda tidak menambah hitungan, komentar dapat disembunyikan, dan persetujuan dari formulir revisi lama ditolak. Suite lengkap dengan PostgreSQL dan Chrome lulus. Rute katalog, pengajuan, dan moderasi diverifikasi di server lokal setelah migrasi 005 dan pembaruan grant.
