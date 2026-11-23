# EduQuest Admin CRUD Implementation Plan

**Tujuan** — Admin dapat mengelola topik, kuis, soal, pilihan jawaban, dan akun siswa dari antarmuka EduQuest. Riwayat kuis dan audit dapat ditinjau.

**Arsitektur** — PHP native, PostgreSQL, Tailwind, dan koneksi tulis tersendiri untuk operasi admin setelah validasi role dan CSRF. Koneksi web umum dipakai untuk login dan akses siswa.

- [x] Buat tes HTTP dari login admin sampai membuat topik, membuat kuis, menulis soal, menerbitkan versi, mengerjakan sebagai siswa, dan memeriksa riwayat setelah versi baru diterbitkan.
- [x] Tambahkan skema draf, penerbitan dan arsip kuis. Gunakan transaksi untuk publikasi serta larangan mengubah isi versi terbit di PostgreSQL. Percobaan yang berjalan atau telah selesai tetap memakai versi yang sama.
- [x] Implementasikan panel topik, kuis dan soal, siswa, serta riwayat. Wajibkan formulir POST dengan CSRF dan konfirmasi penghapusan. Topik yang masih dipakai ditolak. Penghapusan kuis berlaku untuk draf. Kuis terbit diarsipkan. Akun siswa bersejarah dapat dinonaktifkan.
- [x] Beri koneksi editor hak minimum, sembunyikan hash dan rahasia dari tampilan, batasi editor pada akun siswa, dan catat perubahan admin. Pengelolaan akun admin tetap lewat CLI.
- [x] Jalankan regresi kuis dan keamanan, tes CRUD, build, dan verifikasi server lokal. Tutor AI, Komunitas, dan Mentor tetap menjadi pekerjaan modul tersendiri.

Verifikasi akhir mencakup suite dengan role web dan editor terbatas, editor kuis melalui browser desktop dan ponsel, penolakan publikasi versi kosong, perlindungan formulir lama, serta kelanjutan percobaan setelah penerbitan versi baru dan pengarsipan. Migrasi lokal mempertahankan tiga kuis awal dan satu percobaan yang sudah ada. Semua halaman admin utama merespons pada server aktif.
