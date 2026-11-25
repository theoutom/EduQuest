# Validasi lokal MVP pilot EduQuest

Validasi 24 November 2026, WIB. Source aplikasi berasal dari commit `30175d3`. Suite browser di workspace menambahkan uji keyboard. Semua data uji sintetis.

| Cakupan MVP | Bukti uji lokal |
| --- | --- |
| Dashboard dan jadwal | Progres dan streak WIB, lanjut sesi, tantangan harian, serta jadwal yang diterbitkan admin dan terlihat oleh siswa. Jadwal draf tetap privat. |
| Daftar Kuis dan Profil | Filter katalog, simpan dan hapus simpanan, perubahan profil akademik, serta akses admin ke pengelolaan akun. |
| Pengantar dan Pengerjaan | Mulai dan lanjutkan percobaan, tenggat server, jawaban dan tanda ragu-ragu tetap ada setelah muat ulang, pemulihan simpan saat koneksi terganggu, serta pencegahan penulisan terlambat. |
| Hasil dan Pembahasan | Skor, soal benar, salah, atau kosong, akses pemilik, filter, serta keluaran PDF dari tampilan cetak. |
| Tutor | Kontrak 9Router teruji dengan server tiruan lokal. Skenario tanpa provider terkonfigurasi memakai panduan lokal. |
| Bank Soal Komunitas | Pengajuan, moderasi, pencarian latihan, pembahasan, suara, komentar, dan arsip. |
| Cari Mentor | Direktori, konflik slot, pemesanan, konfirmasi, pembatalan, dan tautan pertemuan privat. |
| Admin, akses, dan tampilan | Role web dan editor terpisah. Uji browser desktop dan ponsel, login dengan keyboard, pengerjaan hingga pembahasan dengan keyboard, serta penerbitan kuis oleh admin dengan keyboard. Demo tamu lolos uji isolasi dan penolakan akses fitur privat pada database uji. |

Build menggunakan `npm run build`. Jalankan `npm test` pada PostgreSQL pengembangan dengan role web dan editor [sesuai README](../../README.md#pengujian). Untuk mengulang uji 9Router, atur `EDUQUEST_TEST_9ROUTER=1` sebelum menjalankan suite. Suite membuat server tiruan. Untuk mengulang jalur cadangan, jalankan `node tests/run.mjs tutor` dengan `EDUQUEST_TEST_GATEWAY_UNCONFIGURED=1`. Uji mode tamu secara terpisah dengan `node tests/run.mjs public-demo`.

## Upgrade dan pemulihan

Jalankan `node tests/run.mjs upgrade` pada PostgreSQL pengembangan untuk mengulang uji upgrade. Runner menyiapkan database sementara pada skema `009`, menyimpan sebuah hasil kuis, menjalankan migrasi dua kali, lalu memeriksa daftar migrasi, nilai, pembahasan, dan akses pemilik. Runner menghapus database uji setelah selesai.

Simulasi dimulai dari migrasi `001` sampai `009` dan grant Preview lama. Siswa sintetis menyelesaikan satu kuis pada skema tersebut. `scripts/migrate.php` menerapkan `010` dan `011`, lalu `scripts/runtime-role.php --refresh-grants` memperbarui kedua role. Menjalankan migrasi lagi melaporkan database sudah mutakhir.

Operator menerbitkan jadwal melalui role editor. Setelah PostgreSQL dihentikan secara bersih, cadangan fisik dipulihkan ke direktori PostgreSQL baru dengan versi yang sama. Nilai dan pembahasan lama, jadwal terbit, 11 catatan migrasi, batas akses role web, dan penolakan akses hasil oleh siswa lain tetap cocok.

Latihan pemulihan logis juga lulus dengan dua database lokal yang berisi data sintetis. Arsip terenkripsi berhasil dibuka, lalu 21 tabel aplikasi, 14 sequence, dan daftar 11 migrasi cocok dengan sumber. Nilai dan pembahasan dapat dibuka melalui role web oleh pemilik hasil. Arsip uji tidak membawa sesi aktif atau hash kata sandi. Akun yang dipulihkan perlu diaktifkan kembali dengan kredensial baru.

## Gerbang rilis pilot

Proyek Supabase khusus pilot dan deployment Preview privat sudah tersedia. Uji HTTP dengan akun sintetis mencakup proteksi Vercel, kuis dan pembahasan siswa, penerbitan jadwal, moderasi komunitas, serta privasi pemesanan mentor. Pada 25 November 2026, snapshot logis dari database Supabase pilot dipulihkan ke PostgreSQL lokal. Pemeriksaan mencakup 21 tabel aplikasi, 14 sequence, 11 migrasi, dan akses privat hasil kuis. Hash kata sandi dan sesi aktif dikecualikan, sehingga akun hasil pemulihan membutuhkan kredensial baru. Arsip disimpan terenkripsi untuk pengguna Windows saat ini. Pemeriksaan teknis sembilan soal sudah selesai. Guru masih perlu menilai kesesuaian materi dengan kelas. [Pemetaan deployment](p0-deployment-mapping.md) · [Tinjauan materi](../reviews/kuis-contoh-pilot.md)

Pembersihan sesi berkala belum terpasang pada container Preview. [Catatan operasional](operasional-pilot.md)
