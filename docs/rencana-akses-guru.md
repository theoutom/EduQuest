# Rencana akses guru dan pengelolaan materi

Status pemeriksaan 25 November 2026.

## Yang tersedia

Admin dapat membuat topik dan draf kuis, mengisi soal pilihan ganda, potongan kode, kunci, dan pembahasan, lalu menerbitkannya. Admin dapat mengatur durasi dan nilai lulus, membuat versi revisi, mengarsipkan kuis, mengelola siswa dan jadwal, memoderasi komunitas, serta mengelola mentor. Versi kuis terbit dilindungi agar riwayat dan percobaan lama tetap mengacu pada versinya.

Uji lokal mencakup pembuatan topik, kuis dan soal hingga publikasi serta pengerjaan oleh siswa. Uji browser mencakup pembuatan dan penerbitan kuis di desktop dan ponsel. Preview pilot telah diuji untuk login admin, jadwal, moderasi komunitas dan mentor. Pembuatan kuis dari awal hingga hasil siswa di Preview pilot masih perlu diuji langsung.

Saat ini peran aplikasi hanya `student` dan `admin`. Guru dapat memakai akun admin yang dibuat khusus, dengan seluruh hak admin. Belum ada peran guru dengan hak terbatas. Pengelolaan materi saat ini berbentuk topik, kuis, soal dan pembahasan. Halaman modul bacaan, unggahan dokumen dan pengelolaan kelas belum tersedia.

## Tahap yang disarankan

1. **Validasi alur yang sudah ada.** Di Preview pilot, admin membuat kuis dan soal sintetis, menerbitkan, lalu siswa mengerjakan dan membaca pembahasan. Uji revisi dan akses siswa terhadap draf. Catat hasil serta arsipkan data uji.
2. **Peran guru.** Tambahkan akun `teacher` yang dikelola admin. Tetapkan izin guru untuk membuat dan merevisi materi, menerbitkan kuis, serta melihat hasil sesuai lingkup yang disepakati. Pengelolaan akun admin dan konfigurasi sistem tetap dibatasi untuk admin.
3. **Materi bacaan.** Setelah menentukan jenis materi dengan guru, rancang judul, topik, isi teks, status draf dan terbit, serta keterkaitan dengan kuis. Tentukan terlebih dahulu kebutuhan unggahan dan batas aksesnya.
4. **Verifikasi rilis.** Uji matriks hak siswa, guru dan admin pada setiap endpoint, CSRF, audit, migrasi data lama, revisi bersamaan, dan akses di perangkat kecil. Jalankan suite serta uji end-to-end di Preview sebelum promosi.

Tahap 2 dan 3 adalah rancangan yang belum diimplementasikan. Kepemilikan konten, hak penerbitan, cakupan hasil siswa dan format materi perlu disepakati bersama guru sebelum implementasi.

## Mencoba admin saat ini

Buka [Preview pilot](https://eduquest-pilot-5fgqq36xz-satrioz.vercel.app), masuk melalui autentikasi Vercel, lalu gunakan akun admin pilot. Pilih **Kelola topik** dan **Kelola kuis dan soal**. Akun pilot disimpan secara terenkripsi di komputer pengelola. Demo publik menyediakan alur siswa.
