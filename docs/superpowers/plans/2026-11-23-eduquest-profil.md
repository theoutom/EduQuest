# Profil siswa mandiri

MVP menampilkan sekolah, kelas, jurusan, minat belajar, dan bio yang dapat dikosongkan. Identitas akun tetap dikelola admin. Profil akademik hanya tampil kepada pemilik sesi.

- [x] Uji simpan, muat ulang, kosongkan, tolak input tidak valid, dan pastikan sesi lain tidak bisa melihat atau mengubah profil.
- [x] Tambahkan tabel profil akademik dan beri role web akses hanya pada kolom profil. Tentukan pemilik dari sesi.
- [x] Tampilkan formulir, riwayat kuis, dan statistik pribadi dari percobaan yang telah dinilai. Uji formulir Chrome di lebar ponsel.
- [x] Jalankan suite dengan role terbatas, terapkan migrasi, dan periksa profil instalasi lokal.

`npm run build` dan `npm test` lulus. Uji Chrome menyimpan profil pada lebar 390 piksel. Migrasi `009_student_profiles.sql` dan grant web diterapkan di PostgreSQL lokal. Profil akun siswa dibuka dengan HTTP 200 dan `Cache-Control: no-store`.
